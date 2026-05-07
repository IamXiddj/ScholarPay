// api/logout.js
import { supabase } from '../lib/supabase.js';
import { requireAuth } from '../lib/auth.js';

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') return res.status(200).end();

    const user = requireAuth(req, res);
    if (!user) return;

    await supabase.from('activity_log').insert({
        user_id: user.id,
        action: 'LOGOUT',
        description: 'User logged out',
        ip_address: req.headers['x-forwarded-for'] || 'unknown'
    });

    return res.status(200).json({ success: true });
}
