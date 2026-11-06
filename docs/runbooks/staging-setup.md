# Staging setup on DigitalOcean

One Droplet runs everything with Docker Compose: Caddy (HTTPS), PHP-FPM, the
queue worker, the scheduler, PostgreSQL and Redis. Only ports 22, 80 and 443
are open. Staging is demo-only and must never hold payment credentials or
production provider keys.

## 1. Create the Droplet

- Image: **Ubuntu 24.04 LTS**. Size: **2 GB RAM / 1 vCPU** minimum
  (4 GB recommended). Region near your users (e.g. Frankfurt `fra1`).
- Authentication: **SSH key** (not a password).
- Enable **monitoring** and, for production later, **weekly Droplet backups**.
- Optional: a **Cloud Firewall** allowing only 22 (from your IP), 80 and 443.

## 2. DNS

Create an `A` record for your staging hostname (e.g. `staging.yourdomain.com`)
pointing at the Droplet's IP. Caddy needs it to obtain the HTTPS certificate.

## 3. Bootstrap the server

From your machine (replace the IP and key):

```bash
scp deploy/scripts/bootstrap-droplet.sh root@DROPLET_IP:/root/
ssh root@DROPLET_IP 'bash /root/bootstrap-droplet.sh deploy "$(cat ~/.ssh/id_ed25519.pub)"'
```

Use a **separate key pair** for GitHub Actions deploys
(`ssh-keygen -t ed25519 -f luxbet-deploy -C github-deploy`) and append its
public key to `/home/deploy/.ssh/authorized_keys`.

## 4. Secrets on the server

```bash
ssh deploy@DROPLET_IP
cd /opt/luxbet
nano .env          # from deploy/.env.staging.example
nano backup.env    # from deploy/backup.env.example
chmod 600 .env backup.env
```

- `APP_KEY`: generate one with `php artisan key:generate --show` locally.
- `DB_PASSWORD`, `REDIS_PASSWORD`, `BACKUP_PASSPHRASE`: long random values
  (`openssl rand -base64 36`). Store the backup passphrase in your password
  manager as well.
- `LUXBET_DOMAIN`, `APP_URL`, `ACME_EMAIL`: your staging hostname and email.

## 5. Registry access

Images are private packages in GitHub Container Registry. Create a GitHub
**classic personal access token** with only `read:packages`, then on the
Droplet:

```bash
echo YOUR_TOKEN | docker login ghcr.io -u kemboy-svg --password-stdin
```

## 6. GitHub Actions

In the repository, **Settings → Secrets and variables → Actions**:

| Type     | Name                      | Value                              |
| -------- | ------------------------- | ---------------------------------- |
| Variable | `STAGING_DEPLOY_ENABLED`  | `true`                             |
| Variable | `STAGING_HOST`            | Droplet IP or hostname             |
| Variable | `STAGING_USER`            | `deploy`                           |
| Secret   | `STAGING_SSH_KEY`         | Private key of the deploy key pair |
| Secret   | `STAGING_SSH_KNOWN_HOSTS` | Output of `ssh-keyscan DROPLET_IP` |

Also create an environment named `staging` (Settings → Environments). Add
yourself as a required reviewer if you want to approve each deploy.

Every push to `main` now builds images and deploys them
(`.github/workflows/staging.yml`). To deploy a specific commit, run the
workflow manually with its SHA.

## 7. First deploy and seed data

After the first successful deploy:

```bash
docker compose exec app php artisan db:seed --class=Database\\Seeders\\LocalSimulatorCatalogueSeeder --force
```

Create the first super admin (there is deliberately no self-service way):

```bash
docker compose exec app php artisan tinker
>>> $u = App\Models\User::create(['name' => 'Your Name', 'email' => 'you@example.com', 'password' => 'a-long-temporary-password']);
>>> $u->forceFill(['email_verified_at' => now()])->save();
>>> $u->assignRole('super_admin');
```

Sign in, change the password, set up two-factor authentication, then add
other staff from **Admin → Staff**.

## 8. Backups

Add the cron entries from [backups-and-restore.md](backups-and-restore.md)
and run the first restore drill.

## Checklist

- [ ] `https://staging…` loads with a valid certificate and the demo banner
- [ ] `docker compose exec app php artisan luxbet:preflight` passes
- [ ] `docker compose exec app php artisan luxbet:launch-gate` shows DISABLED
- [ ] Response headers include `Content-Security-Policy` and
      `Strict-Transport-Security`
- [ ] Nightly backup cron installed; first restore drill passed and logged
- [ ] `.env` contains no payment-provider or production provider keys
