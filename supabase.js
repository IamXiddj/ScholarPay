// api/disburse.js — Core MVP function
import { supabase } from '../lib/supabase.js';
import { requireAdmin } from '../lib/auth.js';

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') return res.status(200).end();
    if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

    const user = requireAdmin(req, res);
    if (!user) return;

    const { scholarship_id, student_id, amount_usdc, purpose } = req.body || {};

    // Validate input
    if (!scholarship_id || !student_id || !amount_usdc || !purpose)
        return res.status(400).json({ error: 'All fields are required.' });

    const amountFloat = parseFloat(amount_usdc);
    if (isNaN(amountFloat) || amountFloat <= 0)
        return res.status(400).json({ error: 'Amount must be greater than 0.' });

    const rawAmount = Math.round(amountFloat * 10000000);

    // Fetch scholarship
    const { data: scholarship, error: schErr } = await supabase
        .from('scholarships')
        .select('*')
        .eq('id', scholarship_id)
        .eq('status', 'active')
        .single();

    if (schErr || !scholarship)
        return res.status(404).json({ error: 'Scholarship not found or inactive.' });

    if (rawAmount > scholarship.remaining_amount)
        return res.status(400).json({
            error: `Amount exceeds remaining balance of ${(scholarship.remaining_amount / 10000000).toFixed(2)} USDC.`
        });

    // Fetch student
    const { data: student, error: studErr } = await supabase
        .from('users')
        .select('*')
        .eq('id', student_id)
        .eq('role', 'student')
        .single();

    if (studErr || !student)
        return res.status(404).json({ error: 'Student not found.' });

    if (!student.stellar_address)
        return res.status(400).json({ error: 'Student has no Stellar wallet address.' });

    // Simulate Stellar transaction (replace with real Stellar SDK call for production)
    const simulatedTxHash   = Array.from({ length: 64 }, () => Math.floor(Math.random() * 16).toString(16)).join('');
    const simulatedLedger   = Math.floor(Math.random() * 9999999) + 50000000;

    // Insert disbursement record
    const { data: disbursement, error: disbErr } = await supabase
        .from('disbursements')
        .insert({
            scholarship_id,
            student_id,
            admin_id:         user.id,
            amount:           rawAmount,
            purpose,
            stellar_tx_hash:  simulatedTxHash,
            ledger_sequence:  simulatedLedger,
            status:           'confirmed'
        })
        .select()
        .single();

    if (disbErr) return res.status(500).json({ error: disbErr.message });

    // Deduct from scholarship remaining balance
    const newRemaining = scholarship.remaining_amount - rawAmount;
    const newStatus = newRemaining <= 0 ? 'depleted' : 'active';

    await supabase
        .from('scholarships')
        .update({ remaining_amount: newRemaining, status: newStatus })
        .eq('id', scholarship_id);

    // Log activity
    await supabase.from('activity_log').insert({
        user_id: user.id,
        action: 'DISBURSE',
        description: `Disbursed ${amountFloat} USDC to student ${student.name} from scholarship ${scholarship.name}. Tx: ${simulatedTxHash}`
    });

    return res.status(200).json({
        success: true,
        disbursement: {
            ...disbursement,
            student_name:      student.name,
            stellar_address:   student.stellar_address,
            scholarship_name:  scholarship.name,
            amount_usdc:       amountFloat
        }
    });
}
