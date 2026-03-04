-- Quita solo los datos que añadió seed_demo_data.sql (5 partidos de prueba y sus MOTM).
-- No toca roster, categorías, torneos ni estadísticas/habilidades de jugadores.

USE if0_41281527_valenciacf;

-- Borrar los 5 partidos insertados por el seed (sede_id NULL y estos rivales).
-- El CASCADE de motm_votaciones elimina también las votaciones y nominados asociados.
DELETE FROM juegos
WHERE sede_id IS NULL
  AND rival IN (
    'Baytown United',
    'Katy FC',
    'Rangers FC',
    'North Shore FC',
    'Challenge United'
  );
