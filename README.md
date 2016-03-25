Access Grant for Elgg
=====================
![Elgg 1.11+](https://img.shields.io/badge/Elgg-1.11+-orange.svg?style=flat-square)
![Elgg 2.0+](https://img.shields.io/badge/Elgg-2.0+-orange.svg?style=flat-square)

## Features

 * Allow users to access content without modifying its access id or expadning access collections

## Usage

To allow user to access an entity that he/she wouldn't normally have access to,
create a new `access_grant` relationship between them:

```php
add_entity_relationship($entity->guid, 'access_grant', $user->guid);
```

To revoke access, remove the relationship.