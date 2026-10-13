FROM golang:1.27.2 AS privilege-helper
ENV CGO_ENABLED=0
RUN go install github.com/tianon/gosu@1.19 && mkdir -p /licenses && cp /usr/local/go/LICENSE /licenses/Go-LICENSE && find /go/pkg/mod -name LICENSE -exec cp --parents {} /licenses/ \;

FROM mysql:8.4
# mysqlsh's bundled Python stack is not used by this application or entrypoint.
# Preserve the MySQL server/client and rebuild the existing privilege helper with patched Go.
RUN microdnf remove -y mysql-shell && microdnf clean all
COPY --from=privilege-helper /go/bin/gosu /usr/local/bin/gosu
COPY --from=privilege-helper /licenses/ /usr/share/licenses/krishidisha-privilege-helper/
RUN chmod 0755 /usr/local/bin/gosu && gosu nobody true && gosu mysql id
