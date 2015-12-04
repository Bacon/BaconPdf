# Bacon PDF

[![Build Status](https://api.travis-ci.org/Bacon/BaconPdf.png?branch=master)](http://travis-ci.org/Bacon/BaconPdf)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bacon/BaconPdf/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bacon/BaconPdf/?branch=master)
[![Coverage Status](https://coveralls.io/repos/Bacon/BaconPdf/badge.svg?branch=master&service=github)](https://coveralls.io/github/Bacon/BaconPdf?branch=master)
[![Documentation Status](https://readthedocs.org/projects/baconpdf/badge/?version=latest)](http://baconpdf.readthedocs.org/en/latest/?badge=latest)

## Introduction
BaconPdf is a new PDF library for PHP with a clean interface. It comes with both writing and reading capabilities for
PDFs up to version 1.7.

## Documentation
You can find the latest documentation over at Read the Docs:
https://baconpdf.readthedocs.org/en/latest/

## Running benchmarks
When doing performance sensitive changes to core classes, make sure to run the benchmarks before and after making your
changes to ensure that they don't cause a huge impact:

```bash
php vendor/bin/athletic -p benchmark -b vendor/autoload.php
```