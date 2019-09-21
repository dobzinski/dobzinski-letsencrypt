#!/bin/sh

# Base
DIR="/opt/cert/pem"
LIMIT="/opt/cert/limit"
LOG="/var/log/letsencrypt/renew.log"

# Letsencrypt
LE="/etc/letsencrypt/live"

# Cert list
LIST=("desenvolvimento.prf.gov.br" "homologacao.prf.gov.br" "teste.prf.gov.br" "treinamento.prf.gov.br")

# Cert file
FILE=("fullchain.pem" "privkey.pem")
CONTROL="fullchain.pem"

# Cert renew
echo "" >> ${LOG}
echo "" >> ${LOG}
echo "Start Script: `date '+%Y-%m-%d %H:%M:%S'`" >> ${LOG}
echo "---" >> ${LOG}
echo "" >> ${LOG}
/usr/bin/certbot renew >> ${LOG} 2>&1

# Run
for d in "${LIST[@]}"
do
	if [ ! -d "${DIR}/${d}" ]; then
		/usr/bin/mkdir "${DIR}/${d}"
		/usr/bin/sleep 2
	fi

	if [ ! -f "${DIR}/${d}/${f}" ]; then
		/usr/bin/touch "${DIR}/${d}/${CONTROL}"
		/usr/bin/sleep 2
	fi
	SAVE=$(/usr/bin/md5sum "${DIR}/${d}/${CONTROL}" | /usr/bin/cut -d" " -f1)
	ENABLE=$(/usr/bin/md5sum "${LE}/${d}/${CONTROL}" | /usr/bin/cut -d" " -f1)
	if [ ${SAVE} != ${ENABLE} ]; then
		/usr/bin/rm -f "${DIR}/${d}.tar"
		/usr/bin/rm -f "${LIMIT}/${d}"
		/usr/bin/sleep 2
		for f in "${FILE[@]}"
		do
			/usr/bin/cp -Lfp ${LE}/${d}/${f} ${DIR}/${d}/
		done
		/usr/bin/tar cf "${DIR}/${d}.tar" -C "${DIR}/${d}" .
		/usr/bin/openssl x509 -enddate -noout -in ${DIR}/${d}/${CONTROL} | /bin/cut -d"=" -f2 > ${LIMIT}/${d}
	fi
done

exit 0
