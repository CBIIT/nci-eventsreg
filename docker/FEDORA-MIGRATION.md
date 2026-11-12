# nci-eventsreg Docker directory: Fedora conversion

Use with the Fedora 45 drupal-docker base built in the preceding step. This archive replaces the contents of the project's docker directory; the application files, Composer manifests/lock, settings patch, custom GroupRole.php, and supplied Drupal assets remain unchanged.

## Build

The default BASE_IMAGE remains base:latest for compatibility with the existing pipeline. That tag MUST now refer to your converted Fedora base. Override it with the actual local or ECR Fedora base tag. A remote CI worker cannot use a local Docker tag built on your laptop.

From the project root:

```sh
docker build --platform linux/amd64 --no-cache \
  --build-arg BASE_IMAGE=drupal-docker:fedora45 \
  -t nci-eventsreg:fedora45 ./docker
```

For ECR use --build-arg BASE_IMAGE=YOUR_ECR_BASE_IMAGE:YOUR_FEDORA_TAG. Build base and child for the same architecture used by ECS.

## Changes

- Removes Alpine apk/module installs already covered by the Fedora base; explicitly adds util-linux for su.
- Installs the vhost into /etc/httpd/conf.d and keeps Fedora's main config/module loading/PHP handler.
- Converts httpd.conf into a supplemental conf.d fragment preserving the original forwarded-client-IP rule, log format, server token/signature choices, log level and Proxy header removal. Logs go to container stdout/stderr.
- Preserves HSTS, forwarded HTTPS handling, .htaccess and /var/www/drupal/web DocumentRoot from 000-default.conf.
- Copies the supplied PHP INI files into /etc/php.d.
- Starts/supervises PHP-FPM, Postfix and Apache in the application entrypoint. Postfix no longer starts during docker build.
- Preserves the original Drupal startup initialization and Asset Injector regeneration order. Does not call the base entrypoint or perform a second cache rebuild afterward.
- Installs dependencies from the supplied composer.lock rather than running an extra composer require before that manifest is copied. AWS SDK is already declared in composer.json.
- Uses postconf to set the relay while preserving Fedora's packaged Postfix defaults.
- Installation marker is written only after successful setup. Shell fails on installation errors.
- Adds build checks for PHP 8.5, loaded libxml2 >= 2.15.4, Composer platform requirements, Apache and PHP-FPM syntax.

## Preserved behavior needing awareness

- The startup script still performs database/config changes, including the existing conditional Honeypot schema override. These are carried over, not redesigned or validated against the live database.
- Existing startup commands mostly retain their original continue-on-error behavior; explicit updb/Honeypot failures still exit.
- ldap.conf is retained but NOT copied into /etc/openldap/ldap.conf because the original Dockerfile did not copy it either. Do not assume that file is active. The new base uses Fedora's default LDAP trust configuration; NIH CA trust may need configuration to restore verified LDAP login. No TLS verification bypass is introduced by this conversion.
- scripts/install.sh and *2 backup files are preserved unchanged and are not invoked by the Dockerfile or entrypoint. The legacy scripts/install.sh refers to /opt/drupal and drupaldocker; do not run it as part of this /var/www/drupal conversion.
- Custom GroupRole.php overwrite is preserved.
- Original healthcheck is preserved and checks HTTP transport rather than requiring 2xx.
- Some startup commands intentionally contain /var/www/drupal paths; changing drupal_root alone will not relocate the entire application.

## Test the built image

```sh
docker run --rm nci-eventsreg:fedora45 php -r 'echo PHP_VERSION, " / libxml ", LIBXML_DOTTED_VERSION, PHP_EOL;'
docker run --rm nci-eventsreg:fedora45 php -m
docker run --rm --entrypoint httpd nci-eventsreg:fedora45 -t
docker run --rm nci-eventsreg:fedora45 php-fpm -t
```

Run in the nonproduction ECS environment with its usual database/S3/secret environment variables. Confirm Drupal pages, cache/assets, LDAP, database updates, private uploads/S3 and relay email. Rescan the exact image in Twistlock before promoting it.

## Validation performed here

Validated shell syntax, JSON parsing, ZIP structure, expected configuration paths and that application files/manifests were not changed. Docker/Podman/PHP/Composer were unavailable in this workspace, so an actual image build, configuration validation against Fedora binaries, dependency resolution and database/LDAP/mail integration testing were not performed.

## Startup correction

The first Fargate run showed Postfix exiting because /var/spool/postfix was absent. The supervisor then intentionally terminated Apache/PHP-FPM. The entrypoint now creates the spool/data roots and runs postfix check before starting services. php-soap is also installed because Fedora's PHP-FPM pool config sets soap.wsdl_cache_dir; the earlier image did not load that extension. These corrections passed shell/static checks but require a new build and Fargate startup test.
