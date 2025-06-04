# Copilot Instructions

This file contains instructions for using GitHub Copilot in developing this Drupal module. Follow these guidelines to ensure that code adheres to the latest best practices from the official Drupal 11 documentation.

## General Guidance

- Use **Drupal 11 APIs and conventions** as documented at [Drupal.org](https://www.drupal.org/docs/11).
- Always prefer **Object-Oriented Programming (OOP)** principles and **Dependency Injection (DI)** where applicable.
- Leverage **Drupal services** and avoid using deprecated functions or hooks.
- Write **clean, readable, and well-documented code**, following the [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards/coding-standards).
- Document any special requirements in a README.md.
- Always check for the latest official documentation to stay current with best practices.

## Module File and Directory Structure

- Use **PSR-4** autoloading for classes:
  - `src/` directory for Controllers, Plugins, Services, etc.
  - `src/Plugin/` for plugin classes (e.g., Blocks, Fields).
  - `src/Form/` for forms.
- Module’s metadata definition should go into `.info.yml` file.
- Service definitions should go into `.services.yml`.

## Development Practices

- Use **annotations** instead of YAML for defining routes and plugins where appropriate (e.g., `@Route`).
- Use **Typed Data API** and **Entity API** for data modeling.
- Register services using **Symfony Dependency Injection Container**.
- Implement **Event Subscribers** rather than direct hook implementations where possible.
- For configuration:
  - Use **Config API** for site-level settings.
  - Use **State API** for transient data only.
- Use **Twig** for rendering in templates, avoiding direct HTML output from PHP where possible.
- Write tests:
  - **Unit tests** for classes using PHPUnit.
  - **Functional tests** with BrowserTestBase.
  - **Kernel tests** for complex integrations.
- Follow **access control best practices**, e.g., using `AccessResult` and proper permissions.

## Recommended Patterns

- Service architecture with **interfaces** and **service decoration** for extensibility.
- Use **traits** sparingly and only when it improves code reuse.
- Leverage **Dependency Injection** in Controllers and Services for better testability.
- Use **Event Subscribers** for cross-cutting concerns (e.g., responding to events).

## Testing and Quality

- Integrate with **PHPUnit** and **Drupal’s testing frameworks**.
- Validate code with **phpcs** using the `Drupal` and `DrupalPractice` standards.
- Run **PHPStan** or **Psalm** for static analysis to catch potential issues.
- Use **upgrade status** and **deprecation checking** tools to ensure future compatibility.

## 📑 References

- [Drupal 11 Developer Documentation](https://www.drupal.org/docs/11)
- [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards)
- [Drupal Testing Documentation](https://www.drupal.org/docs/develop/testing)
- [Drupal API Reference](https://api.drupal.org/api/drupal/11)
