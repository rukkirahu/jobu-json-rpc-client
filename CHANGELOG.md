# Changelog

Library versions are Git tags, independent of the JobU daemon API version.

## 1.1.0 (unreleased)

- Support API 1.5 `run.list.states` in typed requests, with validation and a
  daemon-version guard. Older daemons retain scalar and unfiltered queries.
- Document HTTP method admission for new JobU job payloads and pin the
  supported Deferra protocol revision.
- Publish the library under the MIT license.

## 1.0.0

- Initial stable Composer release, targeting JobU API 1.4.
