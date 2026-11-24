# Contributing

To get started follow these steps:

1. `git clone git@github.com:humanmade/smart-media.git` or fork the repository and clone your fork.
1. `cd smart-media`
1. `npm install`
1. `npm run start` for the development server
1. `npm run build` to build the assets

You should then start working on your fork or branch.

## Running the PHP tests

The PHP integration tests are run manually and are not part of CI. See [tests/README.md](./tests/README.md) for how to set them up and run them.

## Making a pull request

When you make a pull request it will be reviewed. You should also update the `CHANGELOG.md` - add lines after the title such as `- Bug: Fixed a typo #33` to describe each change and link to the issues if applicable.

If the change should be applied to previous versions, such as a bugfix, add label or request that labels such as `backport v0-4-branch` are added to the pull request. New pull requests to those branches will be created automatically.

## Making a new release

Releases are built and tagged by a GitHub Actions workflow. Tags are immutable, so a tag can't be re-created once it exists.

1. Checkout the target release branch such as `v0-4-branch`
  - If the target release branch is the latest stable then use the `master` branch, and backport the changes
2. Add a title heading for the new version, such as `## v0.5.14`, above the latest updates in `CHANGELOG.md`. This plugin follows semver versioning.
  - For small backwards compatible changes like bug fixes update the patch version
  - For changes that add functionality without changing existing functionality update the minor version
  - For breaking or highly significant changes update the major version
3. Commit and push the changes
  - If making the changes against `master` create a pull request instead and backport it to the latest stable branch
4. Go to the Actions tab, select the "Release" workflow and choose "Run workflow". Select the target release branch, and enter the version without a leading `v` (for example `0.5.14`).

The workflow builds the assets, replaces the `__VERSION__` placeholder in `plugin.php` with the version, then creates the tag and GitHub release. Don't put a version number in `plugin.php` by hand. If the workflow fails after the tag is created, run it again with the same version and it will pick up the existing tag.

You may need to repeat this process when backporting fixes to multiple release branches.

> **Warning:** Only `master` and branches that have the new "Release" workflow can be released this way. Older release branches such as `v0-4-branch` still have the previous workflow, which runs when a tag is pushed. Do not create or push a tag on those branches by hand. Packagist treats tags as immutable and may lock the unbuilt commit before the workflow moves the tag. If one of those branches needs a release, first apply the `__VERSION__` placeholder and release workflow changes to it.
