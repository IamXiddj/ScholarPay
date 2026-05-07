// api/login.js
import { supabase } from '../lib/supabase.js';
import { signToken } from '../lib/auth.js';
import bcrypt from 'bcryptjs';

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') return res.status(200).end();
    if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

    const { email, password } = req.body || {};

    if (!email || !password)
        return res.status(400).json({ error: 'Email and password are required.' });

    // Fetch user by email
    const { data: user, error } = await supabase
        .from('users')
        .select('*')
        .eq('email', email.toLowerCase().trim())
        .single();

    if (error || !user)
        return res.status(401).json({ error: 'Invalid email or password.' });

    // Verify password
    const valid = await bcrypt.compare(password, user.password);
    if (!valid)
        return res.status(401).json({ error: 'Invalid email or password.' });

    // Log activity
    await supabase.from('activity_log').insert({
        user_id: user.id,
        action: 'LOGIN',
        description: 'User logged in',
        ip_address: req.headers['x-forwarded-for'] || 'unknown'
    });

    // Sign JWT
    const token = signToken({
        id:              user.id,
        name:            user.name,
        email:           user.email,
        role:            user.role,
        stellar_address: user.stellar_address
    });

    return res.status(200).json({
        token,
        user: {
            id:              user.id,
            name:            user.name,
            email:           user.email,
            role:            user.role,
            stellar_address: user.stellar_address,
            institution:     user.institution
        }
    });
}
