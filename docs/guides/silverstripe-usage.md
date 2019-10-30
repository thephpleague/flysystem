---
layout: default
permalink: /docs/guides/silverstripe-usage/
title: SilverStripe Usage
---
Flysystem comes bundled with SilverStripe 4 and newer.

SilverStripe uses a thin wrapper around Flysystem adapters for implementing public and private files.

Per default files are saved locally. There are custom adapters for SilverStripe available:

* <a href="https://github.com/silverstripe/silverstripe-s3">AWS S3</a>
* <a href="https://github.com/obj63mc/silverstripe-google-cloud-storage">Google Cloud Storage</a>

More adapters can be found at <a href="https://packagist.org/packages/cloudinary/cloudinary_php?query=flysystem&type=silverstripe-vendormodule">packagist.org</a>.

More information can be found in the <a href="https://docs.silverstripe.org/en/4/developer_guides/files/file_storage/">SilverStripe documentation</a>.

<div class="flex my-6">
    <a target="_blank" href="https://silverstripe.org" class="flex-no-grow w-1/3 bg-white rounded shadow-md mr-4 overflow-hidden">
        <img src="/img/silverstripe-logo-only-light-web.png" class="w-full" alt="Silverstripe.org   "/>
        <span style="background-color: #142237" class="text-center text-xl hidden sm:block py-4 text-white">Silverstripe.org</span>
    </a>
</div>