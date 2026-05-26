---
layout: default
title: Path Traversal
permalink: /docs/usage/path-traversal/
---

Path traversal is a security consideration. Flysystem protected against escaping the configured root path, but (by
default) allows relative path resolution inside said configured root. This means, within the configured root path,
relative paths are allowed and resolved.

To make this concrete. Provided your adapter has a configured root path of '/documents/', when calling
`$fs->read('../outside.txt')`, there will be a path traversal exception thrown. However, when calling
`$fs->read('deeper/../inside.txt')`, the path will be resolved to '/documents/inside.txt' and the file will be read.

This security feature is implemented across most adapters and enforced by the Filesystem class. This is why you should
never interact with an adapter directly, but always through the Filesystem instance to ensure security and consistency.


