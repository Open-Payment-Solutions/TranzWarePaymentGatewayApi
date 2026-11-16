FROM python:3.13-alpine
RUN apk add --no-cache openssl
WORKDIR /mock
COPY server.py certificates.sh ./
CMD ["sh", "certificates.sh"]
