# Changelog

## [0.3.1](https://github.com/bambamboole/laravel-webhooks/compare/0.3.0...0.3.1) (2026-08-08)


### Bug Fixes

* correct release-please manifest to actual latest version ([d914364](https://github.com/bambamboole/laravel-webhooks/commit/d9143640cde8fb56fb4f82d652ae57972630453b))
* correct release-please manifest to actual latest version ([a5068f2](https://github.com/bambamboole/laravel-webhooks/commit/a5068f2cc4eaaf730d150a7c2e93ada7b354f12d))
* document tenant_id property and index the column ([043b25f](https://github.com/bambamboole/laravel-webhooks/commit/043b25ff07dd0888edc3a68852a7a15a5a61df52))
* document tenant_id property and index the column ([55e2151](https://github.com/bambamboole/laravel-webhooks/commit/55e215176c78950c8d9ca5674f64b6c26a8f2657))

## [0.2.1](https://github.com/bambamboole/laravel-webhooks/compare/0.2.0...0.2.1) (2026-08-08)


### Bug Fixes

* document tenant_id property and index the column ([043b25f](https://github.com/bambamboole/laravel-webhooks/commit/043b25ff07dd0888edc3a68852a7a15a5a61df52))
* document tenant_id property and index the column ([55e2151](https://github.com/bambamboole/laravel-webhooks/commit/55e215176c78950c8d9ca5674f64b6c26a8f2657))

## [0.2.0](https://github.com/bambamboole/laravel-webhooks/compare/0.1.0...0.2.0) (2026-08-02)


### ⚠ BREAKING CHANGES

* replace the WebhookPayload DTO with the payload array
* drop the attribute property from WebhookEventDefinition
* remove configurable payload method from the WebhookEvent attribute
* memoize event discovery in the registry

### Features

* add JSON:API-style links to the webhook envelope ([415808d](https://github.com/bambamboole/laravel-webhooks/commit/415808def8f16e53090f7e7ccb8db4a996807424))
* add WebhookDelivery::resend() ([0a63c96](https://github.com/bambamboole/laravel-webhooks/commit/0a63c96a313dc5835610d26886e5b6615f4787ff))
* add webhooks:cache and webhooks:clear commands ([3f7cbf8](https://github.com/bambamboole/laravel-webhooks/commit/3f7cbf82fa7bea03f1e2f2694a80018a3f0e9573))
* add webhooks:events command ([217cf0d](https://github.com/bambamboole/laravel-webhooks/commit/217cf0ddef3402103414b65640e8c18164a17b20))
* add WebhookSubscription::ping() ([582a1b3](https://github.com/bambamboole/laravel-webhooks/commit/582a1b335dc2b3f985316f7d2beed899b232ede8))
* JSON:API-style resource links in the webhook envelope ([0f18002](https://github.com/bambamboole/laravel-webhooks/commit/0f180027fb2f921e1bbdc110fb67ac470557c124))
* support wildcard event patterns in subscriptions ([1c6d417](https://github.com/bambamboole/laravel-webhooks/commit/1c6d4179945d9e19db392a3a554ed34c1257d9be))
* webhook operations (resend, ping, wildcards, discovery cache, events list) ([a3659f6](https://github.com/bambamboole/laravel-webhooks/commit/a3659f676e04eb93f2c175240eb2e27e138dd976))


### Refactoring

* drop the attribute property from WebhookEventDefinition ([3fbd933](https://github.com/bambamboole/laravel-webhooks/commit/3fbd933f56443f18d9137caf700d45e68d343d76))
* inline the delegate-only subscription lookup in the dispatcher ([608f42b](https://github.com/bambamboole/laravel-webhooks/commit/608f42bf16749e298bff257dc8da60927c44cc19))
* let PHP enforce payload method visibility and arity ([feddce6](https://github.com/bambamboole/laravel-webhooks/commit/feddce6a17d4d9ce885db6d71257d4c5b0a03265))
* match subscription events in the database ([d314458](https://github.com/bambamboole/laravel-webhooks/commit/d314458a4f9f751e41a42205336e20b163e6f7c6))
* memoize event discovery in the registry ([f1e7957](https://github.com/bambamboole/laravel-webhooks/commit/f1e79570feae2ecc49c81d363ebbadc669bd743d))
* remove configurable payload method from the WebhookEvent attribute ([f7adf45](https://github.com/bambamboole/laravel-webhooks/commit/f7adf4523ad09cf9a4960bde65e6da9aab41e959))
* replace the WebhookPayload DTO with the payload array ([33ae0f3](https://github.com/bambamboole/laravel-webhooks/commit/33ae0f3e6b98d3768849dae0e9c6d33380fd55c0))
* simplify class discovery iteration ([67ee856](https://github.com/bambamboole/laravel-webhooks/commit/67ee8563281a70f5349725aba9eb1aed038cee83))
* use json_validate() in workbench BoostConfig ([8fb027f](https://github.com/bambamboole/laravel-webhooks/commit/8fb027ff6c939e4861aaab1028f88585efa8b18e))


### Documentation

* document resend, ping, wildcards, and the webhooks commands ([d89e056](https://github.com/bambamboole/laravel-webhooks/commit/d89e0560210f91f9678b49a18546f0cc7b5d8d7b))

## 0.1.0 (2026-08-01)


### chore

* prepare initial release ([7fe996b](https://github.com/bambamboole/laravel-webhooks/commit/7fe996b15d317587e491f036f9e6886eadfcb034))


### Features

* auto-register listeners for discovered webhook events ([3d66ac5](https://github.com/bambamboole/laravel-webhooks/commit/3d66ac51368ad7ba8b67c9939cd3e5c80d912c2f))
* initial package setup with webhook events, subscriptions, and dispatch ([03ac044](https://github.com/bambamboole/laravel-webhooks/commit/03ac0445a24a5e2a9092a34125aa98c5db689080))
* make the webhook delivery log prunable ([da05dee](https://github.com/bambamboole/laravel-webhooks/commit/da05dee8c4c1c61819257670c06af51a199a31bb))
* mark exhausted webhook deliveries as final_failed ([0dd6b54](https://github.com/bambamboole/laravel-webhooks/commit/0dd6b54a347077541021caf9bf2019cd871480cf))
* require webhook-server, uuid primary keys, and webhook delivery log ([07afbba](https://github.com/bambamboole/laravel-webhooks/commit/07afbba1a2aabce041ea8ec57e41bad317d8685e))
