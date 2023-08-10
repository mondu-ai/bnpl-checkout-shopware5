#!/bin/sh

helpFunction()
{
  echo ""
  echo "Usage: $0 -v version"
  echo "\t-v Version of the plugin"
  exit 1 # Exit script after printing help
}

while getopts "v:" opt
do
  case "$opt" in
    v ) version="$OPTARG" ;;
    ? ) helpFunction ;; # Print helpFunction in case parameter is non-existent
  esac
done

if ! echo $version | grep -Eq "^[0-9]+\.[0-9]+\.[0-9]+$"
then
  echo "Invalid version: ${version}"
  echo "Please specify a semantic version with no prefix (e.g. X.X.X)."
  exit 1
fi

echo "Creationg Mond1SWR5"
mkdir Mond1SWR5
echo "Generating zip file"
rsync -r --exclude 'Mond1SWR5' --exclude "exporter.sh" --exclude "activate.sh" --exclude "SHOPWARE.md" --exclude "*.DS_Store" ./* Mond1SWR5
zip -r -D "Mondu-${version}.zip" Mond1SWR5/*    
rm -r Mond1SWR5
echo "Done"
