FROM nginx:1.27-alpine

COPY public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

HEALTHCHECK --interval=10s --timeout=5s --start-period=20s --retries=8 \
    CMD wget -qO- http://127.0.0.1/up >/dev/null || exit 1
