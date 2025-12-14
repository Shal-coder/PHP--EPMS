<?php
/**
 * Payroll Calculator
 * Handles salary calculations including allowances, deductions, bonuses, and taxes
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Employee.php';

class PayrollCalculator {
    private Employee $employee;
    private string $periodStart;
    private string $periodEnd;
    
    private float $baseSalary = 0;
    private float $totalAllowances = 0;
    private float $totalDeductions = 0;
    private float $totalBonuses = 0;
    private float $taxAmount = 0;
    private float $netSalary = 0;
    
    private array $allowanceDetails = [];
    private array $deductionDetails = [];
    private array $bonusDetails = [];

    public function __construct(Employee $employee, string $periodStart, string $periodEnd) {
        $this->employee = $employee;
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
        $this->baseSalary = $employee->base_salary;
    }

    public function calculate(): self {
        $this->calculateAllowances();
        $this->calculateDeductions();
        $this->calculateBonuses();
        $this->calculateTax();
        $this->calculateNet();
        return $this;
    }

    private function calculateAllowances(): void {
        $allowances = $this->employee->getAllowances($this->periodEnd);
        foreach ($allowances as $alw) {
            $this->allowanceDetails[] = [
                'type' => $alw['type'],
                'description' => $alw['description'],
                'amount' => (float)$alw['amount']
            ];
            $this->totalAllowances += (float)$alw['amount'];
        }
    }

    private function calculateDeductions(): void {
        $deductions = $this->employee->getDeductions($this->periodEnd);
        foreach ($deductions as $ded) {
            $this->deductionDetails[] = [
                'type' => $ded['type'],
                'description' => $ded['description'],
                'amount' => (float)$ded['amount']
            ];
            $this->totalDeductions += (float)$ded['amount'];
        }
    }

    private function calculateBonuses(): void {
        $bonuses = $this->employee->getBonuses($this->periodStart, $this->periodEnd);
        foreach ($bonuses as $bonus) {
            $this->bonusDetails[] = [
                'reason' => $bonus['reason'] ?? 'Bonus',
                'date_awarded' => $bonus['date_awarded'] ?? '',
                'amount' => (float)$bonus['amount']
            ];
            $this->totalBonuses += (float)$bonus['amount'];
        }
    }

    private function calculateTax(): void {
        $taxableIncome = $this->baseSalary + $this->totalAllowances + $this->totalBonuses;
        $this->taxAmount = $this->getTaxByClass($taxableIncome, $this->employee->tax_class);
    }

    private function getTaxByClass(float $income, string $taxClass): float {
        // Simple progressive tax brackets
        $brackets = [
            'A' => [ // Standard
                ['limit' => 1000, 'rate' => 0],
                ['limit' => 3000, 'rate' => 0.10],
                ['limit' => 6000, 'rate' => 0.15],
                ['limit' => PHP_FLOAT_MAX, 'rate' => 0.20]
            ],
            'B' => [ // Reduced
                ['limit' => 1500, 'rate' => 0],
                ['limit' => 4000, 'rate' => 0.08],
                ['limit' => 7000, 'rate' => 0.12],
                ['limit' => PHP_FLOAT_MAX, 'rate' => 0.18]
            ]
        ];
        
        $rates = $brackets[$taxClass] ?? $brackets['A'];
        $tax = 0;
        $remaining = $income;
        $prevLimit = 0;
        
        foreach ($rates as $bracket) {
            $taxableInBracket = min($remaining, $bracket['limit'] - $prevLimit);
            if ($taxableInBracket <= 0) break;
            $tax += $taxableInBracket * $bracket['rate'];
            $remaining -= $taxableInBracket;
            $prevLimit = $bracket['limit'];
        }
        
        return round($tax, 2);
    }

    private function calculateNet(): void {
        $this->netSalary = $this->baseSalary + $this->totalAllowances + $this->totalBonuses 
                         - $this->totalDeductions - $this->taxAmount;
    }

    public function getBreakdown(): array {
        return [
            'employee_id' => $this->employee->id,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'base_salary' => $this->baseSalary,
            'allowances' => $this->allowanceDetails,
            'total_allowances' => $this->totalAllowances,
            'deductions' => $this->deductionDetails,
            'total_deductions' => $this->totalDeductions,
            'bonuses' => $this->bonusDetails,
            'total_bonuses' => $this->totalBonuses,
            'tax_class' => $this->employee->tax_class,
            'tax_amount' => $this->taxAmount,
            'gross_salary' => $this->baseSalary + $this->totalAllowances + $this->totalBonuses,
            'net_salary' => $this->netSalary
        ];
    }

    public function saveToPayrollRun(int $payrollRunId): bool {
        $breakdown = $this->getBreakdown();
        $sql = "INSERT INTO payroll_items 
                (payroll_run_id, employee_id, base_salary, total_allowances, total_deductions, 
                 total_bonuses, tax_amount, gross_salary, net_salary, breakdown_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        Database::query($sql, [
            $payrollRunId,
            $this->employee->id,
            $breakdown['base_salary'],
            $breakdown['total_allowances'],
            $breakdown['total_deductions'],
            $breakdown['total_bonuses'],
            $breakdown['tax_amount'],
            $breakdown['gross_salary'],
            $breakdown['net_salary'],
            json_encode($breakdown)
        ]);
        return true;
    }

    // Getters
    public function getBaseSalary(): float { return $this->baseSalary; }
    public function getTotalAllowances(): float { return $this->totalAllowances; }
    public function getTotalDeductions(): float { return $this->totalDeductions; }
    public function getTotalBonuses(): float { return $this->totalBonuses; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getNetSalary(): float { return $this->netSalary; }
    public function getGrossSalary(): float { 
        return $this->baseSalary + $this->totalAllowances + $this->totalBonuses; 
    }
}
