<?php
/**
 * Payslip Generator
 * Generates HTML payslips (can be converted to PDF with browser print)
 */

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/User.php';

class PayslipGenerator {
    
    public static function generate(array $payslipData, Employee $employee): string {
        $user = User::find($employee->user_id);
        $breakdown = json_decode($payslipData['breakdown_json'] ?? '{}', true);
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip - ' . htmlspecialchars($user->getFullName()) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .payslip { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #10b981; font-size: 28px; }
        .header p { color: #666; margin-top: 5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .info-box h3 { color: #333; font-size: 14px; margin-bottom: 10px; text-transform: uppercase; }
        .info-box p { color: #666; margin: 5px 0; }
        .info-box strong { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #333; font-weight: 600; }
        .amount { text-align: right; }
        .total-row { background: #10b981; color: #fff; font-weight: bold; }
        .total-row td { border: none; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px; }
        @media print { body { padding: 0; background: #fff; } .payslip { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="payslip">
        <div class="header">
            <h1>PayrollPro</h1>
            <p>Payslip for Period: ' . date('M d, Y', strtotime($payslipData['period_start'])) . ' - ' . date('M d, Y', strtotime($payslipData['period_end'])) . '</p>
        </div>
        
        <div class="info-grid">
            <div class="info-box">
                <h3>Employee Details</h3>
                <p><strong>Name:</strong> ' . htmlspecialchars($user->getFullName()) . '</p>
                <p><strong>Employee ID:</strong> ' . htmlspecialchars($employee->employee_code) . '</p>
                <p><strong>Department:</strong> ' . htmlspecialchars($employee->department_name ?? 'N/A') . '</p>
            </div>
            <div class="info-box">
                <h3>Payment Details</h3>
                <p><strong>Bank Account:</strong> ' . htmlspecialchars($employee->bank_account ?? 'N/A') . '</p>
                <p><strong>Tax Class:</strong> ' . htmlspecialchars($employee->tax_class) . '</p>
                <p><strong>Pay Date:</strong> ' . date('M d, Y') . '</p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr><th>Description</th><th class="amount">Amount</th></tr>
            </thead>
            <tbody>
                <tr><td>Base Salary</td><td class="amount">$' . number_format($payslipData['base_salary'], 2) . '</td></tr>';
        
        // Allowances
        if (!empty($breakdown['allowances'])) {
            foreach ($breakdown['allowances'] as $alw) {
                $html .= '<tr><td>Allowance: ' . htmlspecialchars(ucfirst($alw['type'])) . '</td><td class="amount">$' . number_format($alw['amount'], 2) . '</td></tr>';
            }
        }
        
        // Bonuses
        if (!empty($breakdown['bonuses'])) {
            foreach ($breakdown['bonuses'] as $bonus) {
                $html .= '<tr><td>Bonus: ' . htmlspecialchars(ucfirst($bonus['type'])) . '</td><td class="amount">$' . number_format($bonus['amount'], 2) . '</td></tr>';
            }
        }
        
        $html .= '<tr style="background:#e8f5e9;"><td><strong>Gross Salary</strong></td><td class="amount"><strong>$' . number_format($payslipData['gross_salary'], 2) . '</strong></td></tr>';
        
        // Deductions
        if (!empty($breakdown['deductions'])) {
            foreach ($breakdown['deductions'] as $ded) {
                $html .= '<tr><td>Deduction: ' . htmlspecialchars(ucfirst($ded['type'])) . '</td><td class="amount">-$' . number_format($ded['amount'], 2) . '</td></tr>';
            }
        }
        
        $html .= '<tr><td>Tax (' . htmlspecialchars($employee->tax_class) . ')</td><td class="amount">-$' . number_format($payslipData['tax_amount'], 2) . '</td></tr>
                <tr class="total-row"><td>Net Salary</td><td class="amount">$' . number_format($payslipData['net_salary'], 2) . '</td></tr>
            </tbody>
        </table>
        
        <div class="footer">
            <p>This is a computer-generated payslip. No signature required.</p>
            <p>Generated on ' . date('F d, Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}
