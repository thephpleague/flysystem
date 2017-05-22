#/bin/bash

# detect base dir
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
  DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE" # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
done
DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"

docker_compose()
{
	docker-compose -p flysystem/tests/functional/ftp -f $DIR/docker/docker-compose.yml $@
}

if [[ $1 == "build" ]]; then
	docker_compose build
	cd -
	exit
fi


docker_compose down

echo 
echo "-----------"
echo "Test ftp adapter with pure-ftpd"
docker_compose up -d pure-ftpd
docker_compose run wait pure-ftpd:21 -t 30
docker_compose run -e FTP_ADAPTER_HOST=pure-ftpd test --testsuite flysystem/tests/functional/ftp
docker_compose down

echo 
echo "-----------"
echo "Test ftp adapter with vsftpd"
docker_compose up -d vsftpd
docker_compose run wait vsftpd:21 -t 30
docker_compose run -e FTP_ADAPTER_HOST=vsftpd test --testsuite flysystem/tests/functional/ftp
docker_compose down

echo 
echo "-----------"
echo "Test completed"