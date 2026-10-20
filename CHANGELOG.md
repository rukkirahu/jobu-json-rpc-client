# Changelog

Library versions are Git tags, independent of the JobU daemon API version.

## 1.2.0

- Support API 1.6 queue revisions, protected updates, and opt-in historical
  `queue.get` reads. Typed queue replies and updates require API 1.6.
- Send the displayed queue revision with operator UI updates and report stale
  conflicts without retrying.

## 1.1.0

- Support API 1.5 `run.list.states` in typed requests, with validation and a
  daemon-version guard. Older daemons retain scalar and unfiltered queries.
- Document HTTP method admission for new JobU job payloads and pin the
  supported Deferra protocol revision.
- Publish the library under the MIT license.

## 1.0.0

- Initial stable Composer release, targeting JobU API 1.4.
