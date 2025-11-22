-- Create table for admin-configurable fixed offer placements
CREATE TABLE IF NOT EXISTS sportoase_fixed_offer_placements (
    id SERIAL PRIMARY KEY,
    weekday INTEGER NOT NULL CHECK (weekday BETWEEN 1 AND 5),
    period INTEGER NOT NULL CHECK (period BETWEEN 1 AND 6),
    offer_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(weekday, period)
);

-- Insert default fixed offers from FIXED_OFFERS config
INSERT INTO sportoase_fixed_offer_placements (weekday, period, offer_name) VALUES
    (1, 1, 'Wochenstart Warm-Up'),
    (1, 3, 'Aktivierung'),
    (1, 5, 'Regulation / Entspannung'),
    (2, 2, 'Aktivierung'),
    (2, 4, 'Konflikt-Reset'),
    (3, 1, 'Aktivierung'),
    (3, 3, 'Regulation / Entspannung'),
    (3, 5, 'Turnen / flexibel'),
    (4, 2, 'Konflikt-Reset'),
    (4, 5, 'Aktivierung'),
    (5, 2, 'Regulation / Entspannung'),
    (5, 4, 'Turnen / flexibel'),
    (5, 5, 'Wochenstart Warm-Up')
ON CONFLICT (weekday, period) DO NOTHING;
