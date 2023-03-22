# stevecomrie/baserow-php Change Log

## 0.0.3 - 2023.03.21

### Added
* Implemented a function that looks for integer values inside payloads being sent to Baserow and attempts to ensure that `json_encode()` properly outputs integer values without being wrapped by quotes. Fixes [an issue introduced by Baserow 1.15](https://gitlab.com/bramw/baserow/-/issues/1653) that interprets quote-wrapped integers as string and prevents updates to single/multi select field value by ID.


## 0.0.2 - 2021.10.31

### Added
* Can now pass an array as a value for query fields, which creates duplictates of the same param name in the query string - which Baserow accepts. Usually only useful with the "OR" condition.
