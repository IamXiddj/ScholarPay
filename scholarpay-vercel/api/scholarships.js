// api/scholarships.js
import { supabase } from '../lib/supabase.js';
import { requireAdmin, requireAuth } from '../lib/auth.js';

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') return res.status(200).end();

    // GET — list all scholarships (admin) or active only (student)
    if (req.method === 'GET') {
        const user = requireAuth(req, res);
        if (!user) return;

        let query = supabase
            .from('scholarships')
            .select(`*, creator:created_by(name)`)
            .order('created_at', { ascending: false });

        if (user.role === 'student') query = query.eq('status', 'active');

        const { data, error } = await query;
        if (error) return res.status(500).json({ error: error.message });
        return res.status(200).json(data);
    }

    // POST — create scholarship (admin only)
    if (req.method === 'POST') {
        const user = requireAdmin(req, res);
        if (!user) return;

        const { name, description, total_amount, token_address } = req.body || {};
        if (!name || !total_amount)
            return res.status(400).json({ error: 'Name and total_amount are required.' });

        const rawAmount = Math.round(parseFloat(total_amount) * 10000000);
        if (rawAmount <= 0)
            return res.status(400).json({ error: 'Amount must be greater than 0.' });

        const { data, error } = await supabase.from('scholarships').insert({
            name,
            description:       description || null,
            total_amount:      rawAmount,
            remaining_amount:  rawAmount,
            token_address:     token_address || null,
            status:            'active',
            created_by:        user.id
        }).select().single();

        if (error) return res.status(500).json({ error: error.message });

        await supabase.from('activity_log').insert({
            user_id: user.id,
            action: 'CREATE_SCHOLARSHIP',
            description: `Created scholarship: ${name}`
        });

        return res.status(201).json(data);
    }

    // PATCH — update status (admin only)
    if (req.method === 'PATCH') {
        const user = requireAdmin(req, res);
        if (!user) return;

        const { id, status } = req.body || {};
        if (!id || !['active','inactive'].includes(status))
            return res.status(400).json({ error: 'Invalid id or status.' });

        const { data, error } = await supabase
            .from('scholarships')
            .update({ status })
            .eq('id', id)
            .select()
            .single();

        if (error) return res.status(500).json({ error: error.message });
        return res.status(200).json(data);
    }

    return res.status(405).json({ error: 'Method not allowed' });
}
