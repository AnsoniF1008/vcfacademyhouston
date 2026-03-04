-- Add score columns to juegos for results (goles_vcf, goles_rival)
ALTER TABLE juegos
    ADD COLUMN goles_vcf INT NULL,
    ADD COLUMN goles_rival INT NULL;
