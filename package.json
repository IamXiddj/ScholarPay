// api/disbursements.js
import { supabase } from '../lib/supabase.js';
import { requireAuth } from '../lib/auth.js';

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') return res.status(200).end();
    if (req.method !== 'GET') return res.status(405).json({ error: 'Method not allowed' });

    const user = requireAuth(req, res);
    if (!user) return;

    const page    = Math.max(1, parseInt(req.query.page || '1'));
    const perPage = 20;
    const from    = (page - 1) * perPage;
    const to      = from + perPage - 1;

    let query = supabase
        .from('disbursements')
        .select(`
            *,
            student:student_id(name, email, stellar_address),
            admin:admin_id(name),
            scholarship:scholarship_id(name)
        `, { count: 'exact' })
        .order('disbursed_at', { ascending: false })
        .range(from, to);

    // Students only see their own
    if (user.role === 'student') {
        query = query.eq('student_id', user.id);
    }

    // Optional filters (admin)
    if (req.query.student_id)    query = query.eq('student_id', req.query.student_id);
    if (req.query.scholarship_id) query = query.eq('scholarship_id', req.query.scholarship_id);
    if (req.query.status)        query = query.eq('status', req.query.status);

    const { data, error, count } = await query;
    if (error) return res.status(500).json({ error: error.message });

    return res.status(200).json({ data, total: count, page, perPage });
}
