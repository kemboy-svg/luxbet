-- Creates the database used by the automated test suite (phpunit.xml).
SELECT 'CREATE DATABASE luxbet_testing OWNER luxbet'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'luxbet_testing')\gexec
