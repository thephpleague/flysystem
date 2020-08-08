unameOut="$(uname -s)"
version="v1.0.1"

case "${unameOut}" in
    Linux*)     url="https://github.com/splitsh/lite/releases/download/${version}/lite_linux_amd64.tar.gz";;
    Darwin*)    url="https://github.com/splitsh/lite/releases/download/${version}/lite_darwin_amd64.tar.gz";;
    *)          url=unknown
esac

if [ "${url}" == "unknown" ]; then
  echo "FAILED";
  exit 1;
fi

wget -O build/split-lite.tar.gz "${url}"
tar -zxpf build/split-lite.tar.gz --directory ./build/
chmod +x build/splitsh-lite
rm build/split-lite.tar.gz




