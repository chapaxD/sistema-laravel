ALTER TABLE producto ADD COLUMN stock integer DEFAULT 0;
UPDATE producto SET stock = 100 WHERE stock = 0; -- Stock inicial de prueba
