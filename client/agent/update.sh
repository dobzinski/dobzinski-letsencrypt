#!/bin/sh

# VARS
DEBUG=true
LOCAL=true
USR="apache"
GRP="apache"
SERVER="https://server.com/letsencrypt"
NAME="www.example.com"
DIR="/opt/agent"
ENABLE="/opt/agent/letsencrypt"
FILE="fullchain.pem"
CN="www.example.com"
LOG="/tmp/agent.log"

## BIG IP
#KEY="privkey.pem"
#DG="dg_cluster"
#STATUS=$(/usr/bin/tmsh -c "show /cm failover-status" | /bin/grep ^Status | /usr/bin/awk '{print $2}')

# SYSTEM
if [ -f "${DIR}/.token" ]; then
	APPLY=false
	TOKEN=$(/bin/cat ${DIR}/.token)
else
	echo "The token was not installed!"
	exit
fi

# CONTROL
if [ ! -d ${ENABLE}/${NAME} ]; then
	/bin/mkdir -p ${ENABLE}/${NAME}
	/bin/sleep 2
	/bin/chown -R ${USR}:${GRP} ${ENABLE}
fi
if [ ! -f ${ENABLE}/${NAME}/${FILE} ]; then
	/bin/touch ${ENABLE}/${NAME}/${FILE}
	/bin/sleep 2
	/bin/chown -R ${USR}:${GRP} ${ENABLE}/${NAME}/${FILE}
fi

# HASH
FROM=$(/usr/bin/md5sum ${DIR}/${NAME}/${FILE} | /bin/cut -d" " -f1)
TO=$(/usr/bin/md5sum ${ENABLE}/${NAME}/${FILE} | /bin/cut -d" " -f1)

# CERTIFICATE
FROMCN=$(/usr/bin/openssl x509 -in ${DIR}/${NAME}/${FILE} -subject -noout 2>/dev/null | /bin/grep ${NAME} | /usr/bin/tr -d ' ' | /bin/awk -F'CN=' '{print $2}' | /bin/cut -d',' -f1 )
TOCN=$(/usr/bin/openssl x509 -in ${ENABLE}/${NAME}/${FILE} -subject -noout 2>/dev/null | /bin/grep ${NAME} | /usr/bin/tr -d ' ' | /bin/awk -F'CN=' '{print $2}' | /bin/cut -d',' -f1)

# CHECK
if [ ${DEBUG} = true ]; then
	DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
	echo "${DATE} - update.sh: Check cert ${NAME}" >> ${LOG}
fi

if [ ${FROM} != ${TO} ]; then

	# LOCAL
	if [ ${LOCAL} = true ]; then

		if [ "$FROMCN" = "$CN" ]; then

			if [ ${DEBUG} = true ]; then
				DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
				echo "${DATE} - update.sh: Update from local cert ${NAME}" >> ${LOG}
			fi

			/bin/cp -f ${DIR}/${NAME}/* ${ENABLE}/${NAME}/
			/bin/sleep 2
			/bin/chown -R "${USR}:${GRP}" ${ENABLE}/${NAME}

		else

			if [ ${DEBUG} = true ]; then
				DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
				echo "${DATE} - update.sh: File type error ${NAME}" >> ${LOG}
			fi
			exit 1

		fi

	# SHARED (INVERT COPY)
	else

		if [ "$TOCN" == "$CN" ]; then

			if [ ${DEBUG} = true ]; then
				DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
				echo "${DATE} - update.sh: Update from shared cert ${NAME}" >> ${LOG}
			fi

			/bin/cp -f ${ENABLE}/${NAME}/* ${DIR}/${NAME}/
			/bin/sleep 2
			/bin/chown -R "${USR}:${GRP}" ${ENABLE}/${NAME}
		else

			if [ ${DEBUG} = true ]; then
				DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
				echo "${DATE} - update.sh: File type error ${NAME}" >> ${LOG}
			fi
			exit 1

		fi

	fi

	OUT=$(/usr/bin/curl -k -s ${SERVER}"/update.php?file="${NAME}"&token="${TOKEN})
	if [ ${DEBUG} = true ]; then
		DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
		if [ ${OUT} = '200' ]; then
			echo "${DATE} - update.sh: Update agent info!" >> ${LOG}
		else
			echo "${DATE} - update.sh: Fail to update agent info!" >> ${LOG}
		fi
	fi

	APPLY=true
fi

# SERVICE
if [ ${APPLY} = true ]; then

	if [ ${DEBUG} = true ]; then
		DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
		echo "${DATE} - update.sh: Reload service to ${NAME}" >> ${LOG}
	fi

 	# COMMAND

	#/bin/systemctl reload httpd.service >/dev/null 2>&1
	#/sbin/service nginx reload >/dev/null 2>&1
	#/bin/systemctl reload nginx.service >/dev/null 2>&1

	# END COMMAND

 	# BEGIN COMMAND CUSTOM ...

 	## BIG IP
	#if [ "$STATUS" == "ACTIVE" ]; then
 		#/usr/bin/tmsh install /sys crypto cert ${CERTNAMEBIGIP} from-local-file ${ENABLE}/${NAME}/${FILE}
		#/usr/bin/tmsh install /sys crypto key ${CERTNAMEBIGIP} from-local-file ${ENABLE}/${NAME}/${KEY}
		#/usr/bin/tmsh save /sys config
		## SYNC DG
		#/usr/bin/tmsh run /cm config-sync to-group ${DG}
	#fi
	## END BIG IP

	# END COMMAND CUSTOM

fi

exit 0
