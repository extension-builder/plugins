# Compiler extension plugin review

The seven `plg_extension_componentbuilder*` plugins already implement
`Joomla\Event\SubscriberInterface` and declare their JCB compiler hooks in
`getSubscribedEvents()`. Their positional and by-reference parameters are an
intentional JCB contract: the host-specific compiler `Event::trigger()` calls
the registered listeners with the original arguments. These hooks do not use
Joomla's deprecated automatic legacy-listener registration. Their names,
subscriptions, argument order, and mutations remain unchanged.

## Privacy compiler correction

Copy the changed methods and the new `isJoomlaSixTarget()` method from
[`ComponentbuilderPrivacyCompiler.php`](../plg_extension_componentbuilderprivacycompiler/src/Extension/ComponentbuilderPrivacyCompiler.php)
back into the corresponding JCB plugin definition. This change belongs to the
compiler plugin's stored source; changing an installed generated component
helper would be overwritten by the next compilation.

`setPrivacyExportRequest()` and `setPrivacyRemoveData()` now select the
following expressions when `Compiler\Config->joomla_version` is exactly `6`:

| Generated operation | Joomla 6 output |
| --- | --- |
| Obtain the database | Joomla Factory container lookup of `DatabaseInterface` |
| Create a query | `$db->createQuery()` |
| Construct the export domain | `Joomla\Component\Privacy\Administrator\Export\Domain` |
| Construct export items and fields | `Joomla\Component\Privacy\Administrator\Export\Item` and `Field` |

Factory, DatabaseInterface, and Domain references use the existing Joomla Power
identities `39403062-84fb-46e0-bac4-0023f766e827`,
`7bd29d76-73c9-4c07-a5da-4f7a32aff78f`, and
`f5e0753c-c8d9-4965-a606-9c45b37a6857`. The emitted placeholders remain split in
the compiler plugin's source so JCB self-compilation does not consume them
prematurely. Item and Field have no entries in the shipped or pinned public
Joomla Power catalogue; their new references are fully qualified. They may be
mapped to maintained Joomla Powers when those definitions are added, without
changing the generated runtime contract.

The generated component helper's privacy methods keep their existing
by-reference helper contract. They are called by the native privacy event
handler; they are not themselves Joomla dispatcher subscribers. Replacing
their signatures with event objects would break the component/plugin boundary.

This selection follows the requested compilation target, independently of the
Joomla version hosting JCB. For targets 3, 4, and 5, the existing output is
retained, including its older database and privacy-class references. The new
target selector is appended after the existing emitters to retain their debug
comment source locations. These generator changes alone do not require a
separate Joomla 4/5 privacy-compiler definition; the repository's native plugin
installation requirements are documented separately.

## Reviewed plugins requiring no handler migration

| Plugin | Finding |
| --- | --- |
| ActionLogCompiler | Its deprecated database calls belong to the Joomla 3 output methods. Targets 4–6 already use the component installer's native action-log helpers. |
| ExportCompiler | Explicit JCB subscriptions already preserve the required component/configuration mutations. |
| FieldOrderingCompiler | Explicit JCB subscription already preserves the by-reference view mutation. |
| HeadersCompiler | Explicit JCB subscriptions and generated header/Power handling remain authoritative. |
| LanguagePackaging | Explicit JCB subscriptions and the injected database trait remain in place. |
| PowersAutoloaderCompiler | Explicit JCB subscription already uses the existing compiler services. |

This review does not replace full compilation of the maintainer's complete JCB
definition package. Regression tests exercise the actual plugin classes and
their generated privacy helper output, rather than a copied implementation.

The original `8fde47e` and migrated emitters were also executed independently
for targets 3, 4 and 5, with debug comments enabled/disabled, tabs/four spaces,
and default, alternate-user-field, custom-link and anonymization settings.
All 48 complete outputs matched byte-for-byte (419,412 bytes), including the
generated source-line comments. The native helper tests execute export and
removal behavior using Joomla's actual privacy export objects.
