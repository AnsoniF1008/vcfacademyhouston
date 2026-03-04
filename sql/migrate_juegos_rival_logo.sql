-- Add rival logo URL to juegos for Latest Results display
ALTER TABLE juegos
    ADD COLUMN rival_logo_url VARCHAR(500) NULL AFTER goles_rival;
