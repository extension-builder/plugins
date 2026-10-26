# Joomla 6 native plugin migration

This change prepares the plugins in this repository for native Joomla 6
compilation and installation. It is paired with the compiler regression repair
in `extension-builder/joomla`.

The maintained JCB plugin definitions remain the source of truth. The reviewed
changes here must be copied back into those definitions before rebuilding JCB;
installing a compiler update alone cannot change an existing plugin's stored
`main_class_code`.

## Scope

- Migrate content form handlers to the native Joomla event contract.
- Use native plugin construction, explicit subscriptions, and installer discovery.
- Preserve JCB's custom compiler event contracts and generated Joomla 3–5 output.
- Exercise the actual plugin source and forms in regression tests.
- Record compatibility and the exact changes to transfer back into JCB.

The branch is under development. Validation results and compatibility details
will be completed before review; this document does not claim a complete JCB
self-compilation.
