# Event Task Hub 2.1.1

Self-contained Drupal 10.3 / 11 module. Creates or reuses **Manage My Event**
(machine name `manage_my`), 18 task-status fields, launch dates, notification
email addresses, a speaker bio link, and native task discussions with files.
No View, Webform, Webform Views, Content Sync, Scheduler or Asset Injector is
required. All code dependencies are Drupal core modules.

## Visual update from version 2.0

Version 2.1 adapts the supplied original CSS to the native templates. It restores
three cards: Task Progress, an inline Post an Update form, and Event Update
History with task filtering and items-per-page controls. It retains collapsed
per-task comments. Task order follows the original exported display, status
counts follow the original chip order, and launch dates use November 7, 2026
formatting. The form/history use native Drupal comments, not Webform or Views.

Replace the module folder, then run `vendor/bin/drush cr`. No database update
or configuration import is required when updating from version 2.0. The page
frame retains the supplied theme's 26% banner clearance rule.

## Update the version already installed — do NOT uninstall or import YAML

1. Back up the database and deploy the replacement `event_task_hub` folder to
   `web/modules/custom/event_task_hub`. Replace the old folder completely.
2. From `/var/www/drupal` run:

```sh
vendor/bin/drush en comment file options datetime link -y
vendor/bin/drush cr
vendor/bin/drush updatedb -y
vendor/bin/drush cr
```

The update hook **event_task_hub_update_11001** provisions the content type and
fields even if you deleted the old content type. A fresh installation calls the
same provisioner. Re-running `drush en event_task_hub` on an already-enabled
module does not execute update hooks; use `updatedb`.

If the module is not currently installed, instead run:

```sh
vendor/bin/drush en event_task_hub -y
vendor/bin/drush cr
```

If you already ran update 11001 but intentionally deleted configuration later,
the idempotent setup service can repair missing definitions:

```sh
vendor/bin/drush php:eval '\Drupal::service("event_task_hub.setup")->provision();'
vendor/bin/drush cr
```

This creates missing definitions, not deleted content or files. A database
backup is needed to recover previously deleted nodes. Existing matching fields
and status choices are reused. Conflicting field types cause a clear error.
The default edit/view layouts are adjusted for this module; other view modes
are left in place. Keep a backup of layout customizations.

## Remove the former display code from these pages

Disable the Asset Injector JavaScript **Manage My Event** (`manage_my_event`)
and CSS **Testing Manage** (`testing_manage`). Their old DOM transformation is
replaced by server-rendered templates and a small Drupal behavior.

Unplace the old **Event Update History** View block from the event page in
Structure → Block layout. This module hides the legacy Post an Update Webform
field from its default form/view layout; it does not delete old fields,
Webforms, Views, submissions or module dependencies belonging to other config.

## Give users access

Under People, assign **Event task manager** to users who create/edit event task
pages, and **Event task participant** to users who only read/post discussions.
No users or existing roles are changed automatically. A manager can edit any
Manage My Event node, subject to other site access modules.

Alternatively, grant an existing role:

- Access content
- Access event task discussions
- View comments (`access comments`)
- Post comments
- Skip comment approval (otherwise comments await moderation)
- Appropriate Manage My Event create/edit permissions for editors

Administrator UID 1 has all permissions. Read access to a parent event is still
required, including existing Group/node access rules. Assigning a participant
role does not itself grant access to otherwise restricted event pages.

## Create an event

Visit `/node/add/manage_my` or Content → Add content → Manage My Event. Enter a
title, choose statuses for applicable tasks and add notification addresses
(one per value). Leave a task blank to omit it from the dashboard. Completed
and Approved statuses count toward progress. Save and view the node.

Each task has a closed-by-default Comments disclosure with its own paginated
history. Open it and choose **Add a comment or attachment**. Use the inline Post an Update form to select any open task, or the per-task
link for a dedicated native Drupal form. Both require a plain-text comment
and support optional attachments. It returns to the selected task and opens that discussion.
This release provides a flat discussion, not nested reply threads.

Comments are linked to both the node and a dedicated task comment field;
histories cannot mix tasks or events. Published comments display newest first,
ten per page. Other users' unpublished comments are not shown in the dashboard;
moderators use Drupal's Content → Comments screen. Comment editing/deletion
uses normal Drupal permissions and screens.

## Configure private attachments

Drupal's private files path must already be configured, e.g. in settings.php:

```php
$settings['file_private_path'] = '/var/www/drupal/private';
```

Use a directory outside the web document root, writable by the Apache user.
Keep it on persistent storage in ECS; a container's writable layer is temporary.
Run `drush cr` after changing settings. Without a private path, the comment form
still works but hides uploads and explains why attachments are unavailable.

Maximum attachment size: 50 MB per file, also subject to PHP and proxy upload
limits. Common document/image/ZIP/ICS formats are allowed. Drupal File fields
manage permanent file status, usage and removal. Downloads require access to
the referenced comment and parent node. Temporary upload previews are limited
to the uploading user. Never switch the attachment storage field to public.

## Notifications

New published comments send a plain-text email through Drupal's configured mail
backend to each value of the node's **Notify Emails** field, separately. The
message includes event, task, author, comment and an absolute link back to the
task. Attachments are accessed after signing in; private files are not emailed.
Pending moderated comments send when first published. Edits and republishing
successfully notified comments do not send duplicate notifications. Invalid
recipients are skipped; delivery failures are logged under `event_task_hub`.
No automatic retry queue is included. A working Drupal mail transport is needed.

## Existing submissions

Old Webform submissions are not automatically migrated into native comments.
Keep the old Webform/View available to administrators if you need to consult
that history. Nothing in this upgrade purges or relabels those submissions.
The new comments have no automatic expiry or Webform purge schedule.

## Validate on staging

1. Update from version 1 with the old type missing; confirm `/node/add/manage_my`.
2. Create two event nodes and multiple populated task fields. Check statuses,
   progress, help links and launch date display on desktop and mobile.
3. Post comments to separate tasks; confirm task/event isolation and pagination.
4. Test manager, participant, anonymous and a user without access to the node.
5. Upload/download a private attachment, then verify users without parent-node
   access cannot download it even with a direct file URL.
6. Verify actual email delivery and moderation using your mail backend.
7. Check comments remain after replacing module code. Do not uninstall to update.

The package includes BrowserTestBase integration tests for provisioning,
isolation, posting, mail dispatch and private download access:

```sh
SIMPLETEST_BASE_URL=http://your-test-site \
SIMPLETEST_DB=mysql://user:pass@host/test_database \
vendor/bin/phpunit -c web/core/phpunit.xml.dist \
  web/modules/custom/event_task_hub/tests/src/Functional
```

These need a disposable Drupal test environment and Drupal's development/test
dependencies. Do not point the tests at a production database.

## Scope and limitations

Rendering disables node render caching for fresh, permission-dependent histories.
Each visible task runs count/list queries; measure performance with many tasks
and comments. A contributed theme with a hard-coded node template may need to
print `content.event_tasks` or the normal `content` render array.

Uninstall is blocked while event nodes or native task comments remain, preventing
accidental loss/exposure. Configuration/data are not deleted by normal updates.

The generation environment did not contain PHP, a running Drupal site or a
usable local browser, so a rendered visual comparison could not be completed. JSON,
YAML, task mapping, CSS structure, JavaScript behavior and archive checks ran; the included PHP/Drupal
integration tests still need to run on staging.

## Post an Update card visibility (2.1.1)

The standalone card is disabled in `templates/event-task-hub-post.html.twig`.
Change `{% if false %}` to `{% if true %}` and clear caches to restore it.
The form class, controller, route and rendering code remain present. Per-task
commenting and overall history are unchanged. Core's duplicate bottom comment
links are suppressed for Manage My Event nodes. No database updates are needed.
