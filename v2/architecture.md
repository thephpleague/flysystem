---
layout: default
title: Architecture
permalink: /v2/docs/architecture/
---

## It's all about adapting.

Flysystem uses the  **adapter pattern**. This pattern is especially
useful for mediating API incompatibilities, so it's a perfect fit
for the use-case.

The `League\Flysystem\FilesystemOperator` interface represents the outside
boundary. It defines how you should interact with Flysystem. This layer
provides common functionality that the underlying filesystem adapters
rely on.

The `League\Flysystem\Filesystem` (the main filesystem operator implementation)
uses adapters to do the _real_ work. Every adapter is an implementation of the
`League\Flysystem\FilesystemAdapter` interface. Each of the adapters conform to
the same contract and behavior specifications (enforced by tests).

## Consuming Flysystem

The `League\Flysystem\FilesystemOperator` interface represents the most complete
interface to integrate with. You can distinguish between _reads_ and _writes_ by
hinting on the underlying interfaces:

 - Reading: `League\Flysystem\FilesystemReader`  
 - Writing: `League\Flysystem\FilesystemWriter`
 
 For any of the three interfaces, the composition will look like this:
 
 ```text
|--- Your Code -----------------------------|
|                                           |
|-> |--- Filesystem --------------------|   |
|   |                                   |   |
|   |-> |--- Filesystem Adapter ----|   |   |
|   |   |                           |   |   |
|   |   |---------------------------|   |   |
|   |                                   |   |
|   |-----------------------------------|   |
|                                           |
|-------------------------------------------|
```


