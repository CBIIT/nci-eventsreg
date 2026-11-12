# Override with your Fedora drupal-docker ECR image/tag in CI.
ARG BASE_IMAGE=base:latest
FROM ${BASE_IMAGE}

# PHP modules and httpd-tools are supplied by the converted Fedora base.
# su is needed by the existing Drupal initialization commands.
RUN dnf install -y --setopt=install_weak_deps=False util-linux php-soap unzip \
    && dnf clean all \
    && rm -rf /var/cache/dnf \
    && php -r 'if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 5 || LIBXML_VERSION < 21504) { fwrite(STDERR, "Expected Fedora base with PHP 8.5 and libxml2 >= 2.15.4\n"); exit(1); }'

# Preserve Fedora's main httpd config and its packaged PHP-FPM handler.
COPY 000-default.conf /etc/httpd/conf.d/000-default.conf
COPY httpd.conf /etc/httpd/conf.d/10-eventsreg.conf
COPY memory.ini 00_filesize.ini /etc/php.d/
COPY entrypoint.sh /usr/bin/entrypoint.sh
COPY ./ /tmp/github/docker
COPY ldap.conf /etc/openldap
COPY installdrupal.sh /usr/bin/installdrupal.sh
RUN chmod +x /usr/bin/entrypoint.sh /usr/bin/installdrupal.sh

WORKDIR ${drupal_root}
# aws/aws-sdk-php already belongs to this project's composer.json/lock.
# Install that lockfile through the existing setup script.
RUN /usr/bin/installdrupal.sh \
    && composer check-platform-reqs \
    && php-fpm --test \
    && httpd -t

# Install the complete PDF.js distribution after Drupal setup.
ARG PDFJS_VERSION=6.3.289
RUN mkdir -p "${drupal_root}/web/libraries/pdf.js" \
    && curl --fail --location --retry 3 \
       "https://github.com/mozilla/pdf.js/releases/download/v${PDFJS_VERSION}/pdfjs-${PDFJS_VERSION}-dist.zip" \
       -o /tmp/pdfjs.zip \
    && unzip -q /tmp/pdfjs.zip -d "${drupal_root}/web/libraries/pdf.js" \
    && rm /tmp/pdfjs.zip \
    && chmod -R a+rX "${drupal_root}/web/libraries/pdf.js" \
    && test -s "${drupal_root}/web/libraries/pdf.js/web/viewer.html" \
    && test -s "${drupal_root}/web/libraries/pdf.js/build/pdf.mjs" \
    && printf '%s\n' 'AddType text/javascript .mjs' > /etc/httpd/conf.d/20-pdfjs.conf \
    && httpd -t

EXPOSE 80
# Retain the existing healthcheck behavior; this checks transport, not HTTP status.
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=4 \
  CMD curl -sS -o /dev/null http://127.0.0.1:80/ || exit 1

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
CMD ["httpd", "-D", "FOREGROUND"]
