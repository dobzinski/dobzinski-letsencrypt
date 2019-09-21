#!/bin/sh

# VARS
DEBUG=true
INTERFACE="eth0"
SERVER="https://server.com/letsencrypt"
NAME="www.example.com"
TEMP="/tmp"
DIR="/opt/agent"
FILE="fullchain.pem"
LOG="/tmp/agent.log"

# SYSTEM
HASH=${DIR}"/.token"

# TOKEN
if [ -f ${HASH} ]; then
	TOKEN=$(/bin/cat ${HASH})
else
	if [ -f "/sys/class/net/${INTERFACE}/address" ]; then
		TOKEN=$(/bin/cat /sys/class/net/${INTERFACE}/address | /usr/bin/md5sum | /bin/cut -d" " -f1)
		echo ${TOKEN} > ${HASH}
	else
		echo "The interface was not found! Please, check INTERFACE variable..."
		exit
	fi
fi

# LOCAL FOLDER
if [ ! -d ${DIR}/${NAME} ]; then
	/bin/mkdir ${DIR}/${NAME}
	/bin/sleep 2
	/bin/touch ${DIR}/${NAME}/${FILE}
	/bin/sleep 2
fi

# DOWNLOAD FOLDER
if [ ! -d ${TEMP}/${NAME} ]; then
	/bin/mkdir -p ${TEMP}/${NAME}
	/bin/sleep 2
fi

# DOWNLOAD
if [ ${DEBUG} = true ]; then
	DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
	echo "${DATE} - check.sh: Start check new cert ${NAME}" >> ${LOG}
fi
/usr/bin/curl -k -s "${SERVER}/download.php?file=${NAME}&token=${TOKEN}" --output "${TEMP}/${NAME}/cert.tar"
OUT=$(/bin/cat ${TEMP}/${NAME}/cert.tar)
TYPE=$(/usr/bin/file ${TEMP}/${NAME}/cert.tar | /bin/cut -d" " -f2)

# REGISTER (NEED MANUAL RUN AGAIN)
if [ ${TYPE} = "ASCII" ]; then
	if [ ${OUT} = "402" ]; then
		if [ ${DEBUG} = true ]; then
			DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
			echo "${DATE} - check.sh: Registry agent to cert ${NAME}" >> ${LOG}
		fi
		INSTALL=$(/usr/bin/curl -k -s "${SERVER}/install.php?token=${TOKEN}")
		DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
		if [ ${INSTALL} = 'Ok!' ]; then
			echo "Agent registred! Please, contact the admin..."
			exit 0
		else
			echo "Error to register!"
			exit 1
		fi
	elif [ ${OUT} = "401" ]; then
		echo "Unauthorized!"
		exit 1
	elif [ ${OUT} = "404" ]; then
		echo "Download Error!"
		exit 1
	fi

# UPDATE
else
	if [ -f ${TEMP}/${NAME}"/cert.tar" ]; then
		/bin/tar xf ${TEMP}"/"${NAME}"/cert.tar" -C ${TEMP}"/"${NAME}"/"
		if [ $? -eq 0 ]; then
			LOCAL=$(/usr/bin/md5sum ${DIR}/${NAME}/${FILE} | /bin/cut -d" " -f1)
			DOWNLOAD=$(/usr/bin/md5sum ${TEMP}/${NAME}/${FILE} | /bin/cut -d" " -f1)
			if [ ${LOCAL} != ${DOWNLOAD} ]; then
				if [ ${DEBUG} = true ]; then
					DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
					echo "${DATE} - check.sh: Copy new cert ${NAME}" >> ${LOG}
				fi
				/bin/rm -f ${TEMP}/${NAME}/cert.tar
				/bin/mv -f ${TEMP}/${NAME}/* ${DIR}/${NAME}/
				/bin/rmdir ${TEMP}/${NAME}
			else
				/bin/rm -f ${TEMP}/${NAME}/*
				/bin/rmdir ${TEMP}/${NAME}
			fi
		else
			if [ ${DEBUG} = true ]; then
				DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
				echo "${DATE} - check.sh: Extract error ${NAME}" >> ${LOG}
			fi
			exit 1
		fi

	else
		if [ ${DEBUG} = true ]; then
			DATE=$(/bin/date '+%Y-%m-%d %H:%M:%S')
			echo "${DATE} - check.sh: Temp file not found ${NAME}" >> ${LOG}
		fi
		exit 1
	fi
fi

exit 0
