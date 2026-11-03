---
name: New Bolt protocol version
about: Track implementation of a new Bolt protocol version
title: 'New Bolt protocol version: '
labels: enhancement
assignees: stefanak-michal

---

## New Bolt Protocol Version: <!-- e.g. 6.2 -->

Please review the full Bolt protocol specification and implement support for the new version.

---

## Implementation Checklist

### Protocol class (`src/protocol/`)
- [ ] Create new protocol class (e.g. `VX_Y.php`) in `src/protocol/`
  - [ ] Create traits for new protocol methods if there are any new ones
  - [ ] Define new `AvailableStructures` if there are any new structures
  - [ ] Define new `ServerStateTransition` if there are any new state transitions
  - [ ] Override `getPackStreamVersion()` with the correct PackStream version

### PackStream (`src/packstream/`)
- [ ] Create new `Packer` and `Unpacker` classes if there is a new PackStream version

### Core (`src/Bolt.php`)
- [ ] Update the default negotiated versions in `Bolt::__construct()` — update the `setProtocolVersions(...)` call to include the new Bolt version as the first (preferred) option

### Tests (`tests/`)
- [ ] Update `tests/TestLayer.php`
  - [ ] Add a new entry to `getCompatibleBoltVersion()` mapping the corresponding Neo4j server version threshold to the new Bolt version number
  - [ ] Update the `sayHello()` method if the login/authentication concept has changed
- [ ] Add new tests to cover changes in the new Bolt and PackStream version (follow the structure of existing tests)

### CI / GitHub Actions
- [ ] Add or update a Neo4j workflow file under `.github/workflows/` (e.g. `neo4j.<year>.yml`) to run integration tests against a Neo4j image that ships the new Bolt version

---

> **Reference links:**
> - [Bolt Compatibility](https://neo4j.com/docs/bolt/current/bolt-compatibility/)
> - [Bolt Protocol Specification](https://neo4j.com/docs/bolt/current/)
> - [PackStream Specification](https://neo4j.com/docs/bolt/current/packstream/)
