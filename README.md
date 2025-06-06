> [!WARNING]
> Archived: This module was moved into drupal.org
> 
> https://www.drupal.org/project/form_reference_field
> 
> https://git.drupalcode.org/project/form_reference_field

---

# Form Reference Field

This module provides a custom field type for Drupal that allows entities to reference and embed module defined forms directly within content. This enables site builders to easily associate forms with nodes, users, or other entities, improving flexibility for collecting user input and managing form submissions.

## Features
- Adds a field type for referencing forms.
- Supports embedding forms in entity displays.
- Integrates with Drupal's field and entity API.

## Requirements
- Drupal 10 or higher

## Installation
1. Place this module in your modules directory (e.g. `web/modules/custom`) or fetch via `composer`.
2. Enable the module via the Drupal admin UI or Drush.

## Usage
- Add the *Form Reference Field* to your desired content type or entity form.
- Configure the field to select which forms can be referenced.
- Display the referenced form in your entity view modes.
- Create a content item and select the form to embed it.

## Roadmap

- Add support for extracting and passing entities from context to entity forms.
- Implement permissions for who can view and submit referenced forms.
- Add support for passing arguments to the referenced forms.

## Maintainers
- Eric Aguayo [ericmaster](https://www.drupal.org/u/ericmaster)
