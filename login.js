// api/students.js
import { supabase } from '../lib/supabase.js';
import { requireAdmin, requireAuth } from '../lib/auth.js';
import bcrypt from 'bcryptjs';

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') return res.status(200).end();

    // GET — list students (admin) or own profile (student)
    if (req.method === 'GET') {
        const user = requireAuth(req, res);
        if (!user) return;

        if (user.role === 'admin') {
            const { data, error } = await supabase
                .from('users')
                .select('id, name, email, stellar_address, institution, created_at')
                .eq('role', 'student')
                .order('created_at', { ascending: false });
            if (error) return res.status(500).json({ error: error.message });
            return res.status(200).json(data);
        } else {
            // Student fetching own profile
            const { data, error } = await supabase
                .from('users')
                .select('id, name, email, stellar_address, institution')
                .eq('id', user.id)
                .single();
            if (error) return res.status(500).json({ error: error.message });
            return res.status(200).json(data);
        }
    }

    // POST — add new student (admin only)
    if (req.method === 'POST') {
        const user = requireAdmin(req, res);
        if (!user) return;

        const { name, email, password, stellar_address, institution } = req.body || {};
        if (!name || !email || !password)
            return res.status(400).json({ error: 'Name, email, and password are required.' });

        if (stellar_address && !/^G[A-Z2-7]{55}$/.test(stellar_address))
            return res.status(400).json({ error: 'Invalid Stellar address format.' });

        // Check email uniqueness
        const { data: existing } = await supabase
            .from('users').select('id').eq('email', email.toLowerCase()).single();
        if (existing)
            return res.status(409).json({ error: 'A user with this email already exists.' });

        const hashed = await bcrypt.hash(password, 10);

        const { data, error } = await supabase.from('users').insert({
            name,
            email:           email.toLowerCase(),
            password:        hashed,
            role:            'student',
            stellar_address: stellar_address || null,
            institution:     institution || null
        }).select('id, name, email, stellar_address, institution').single();

        if (error) return res.status(500).json({ error: error.message });

        await supabase.from('activity_log').insert({
            user_id: user.id,
            action: 'ADD_STUDENT',
            description: `Added student: ${name} (${email})`
        });

        return res.status(201).json(data);
    }

    // PATCH — update stellar wallet (admin or student updating own)
    if (req.method === 'PATCH') {
        const user = requireAuth(req, res);
        if (!user) return;

        const { student_id, stellar_address } = req.body || {};
        const targetId = user.role === 'admin' ? student_id : user.id;

        if (!stellar_address || !/^G[A-Z2-7]{55}$/.test(stellar_address))
            return res.status(400).json({ error: 'Invalid Stellar address.' });

        const { data, error } = await supabase
            .from('users')
            .update({ stellar_address })
            .eq('id', targetId)
            .select('id, name, stellar_address')
            .single();

        if (error) return res.status(500).json({ error: error.message });

        await supabase.from('activity_log').insert({
            user_id: user.id,
            action: 'UPDATE_WALLET',
            description: `Updated wallet for user ${targetId}: ${stellar_address}`
        });

        return res.status(200).json(data);
    }

    return res.status(405).json({ error: 'Method not allowed' });
}
