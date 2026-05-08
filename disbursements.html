// api/stats.js
import { supabase } from '../lib/supabase.js';
import { requireAuth } from '../lib/auth.js';

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') return res.status(200).end();
    if (req.method !== 'GET') return res.status(405).json({ error: 'Method not allowed' });

    const user = requireAuth(req, res);
    if (!user) return;

    if (user.role === 'admin') {
        const [scholarships, students, disbursements] = await Promise.all([
            supabase.from('scholarships').select('id, status'),
            supabase.from('users').select('id').eq('role', 'student'),
            supabase.from('disbursements').select('amount, status, disbursed_at, scholarship_id, student_id, purpose, stellar_tx_hash, student:student_id(name, stellar_address), scholarship:scholarship_id(name)').order('disbursed_at', { ascending: false }).limit(8)
        ]);

        const totalScholarships  = scholarships.data?.length || 0;
        const activeScholarships = scholarships.data?.filter(s => s.status === 'active').length || 0;
        const totalStudents      = students.data?.length || 0;
        const confirmed          = disbursements.data?.filter(d => d.status === 'confirmed') || [];
        const totalDisbursed     = confirmed.reduce((sum, d) => sum + d.amount, 0);

        return res.status(200).json({
            totalScholarships,
            activeScholarships,
            totalStudents,
            totalDisbursed,
            recentDisbursements: disbursements.data || []
        });
    }

    // Student stats
    const { data: myDisbursements } = await supabase
        .from('disbursements')
        .select('amount, status, disbursed_at, scholarship:scholarship_id(name), purpose, stellar_tx_hash, ledger_sequence')
        .eq('student_id', user.id)
        .order('disbursed_at', { ascending: false })
        .limit(5);

    const totalReceived = (myDisbursements || [])
        .filter(d => d.status === 'confirmed')
        .reduce((sum, d) => sum + d.amount, 0);

    return res.status(200).json({
        totalReceived,
        disbursementCount: myDisbursements?.length || 0,
        recentDisbursements: myDisbursements || []
    });
}
