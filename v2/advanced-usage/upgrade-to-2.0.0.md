---
layout: default
title: Upgrade to 2.0.0
permalink: /v2/docs/advanced/upgrade-to-2.0.0/
---

## Removed functionality.

### Filesystem::getMetadata is removed

The `getMetadata` method is removed, in favour of better metadata getter.

### Metadata getters are renamed:

Some were not specific enough, others had incorrect casing.

```diff
// Explains better how to interpret the response
- $lastModified = $filesystem->getTimestamp($path); 
+ $lastModified = $filesystem->lastModified($path);

// More explicit it's only for files.
- $fileExists = $filesystem->has($path); 
+ $fileExists = $filesystem->fileExist($path);

// Correct casing.
- $mimetype = $filesystem->getMimetype($path); 
+ $mimetype = $filesystem->mimeType($path);

// More explicit about what type of size
- $fileSize = $filesystem->getSize($path); 
+ $fileSize = $filesystem->fileSize($path);

// Nicer naming
- $visibility = $filesystem->getVisibility($path); 
+ $visibility = $filesystem->visibility($path);
```


