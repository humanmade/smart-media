# Contributing

To get started follow these steps:

1. `git clone git@github.com:humanmade/smart-media.git` or fork the repository and clone your fork.
1. `cd smart-media`
1. `npm install`
1. `npm run start` for the development server
1. `npm run build` to build the assets

You should then start working on your fork or branch.

## Making a pull request

When you make a pull request it will be reviewed. You should also update the `CHANGELOG.md` - add lines after the title such as `- Bug: Fixed a typo #33` to describe each change and link to the issues if applicable.

If the change should be applied to previous versions, such as a bugfix, add label or request that labels such as `backport v0-4-branch` are added to the pull request. New pull requests to those branches will be created automatically.

## Making a new release


1. Checkout the target release branch such as `v0-4-branch`
  - If the target release branch is the latest stable then use the `master` branch, and backport the changes
2. Update the version number in `plugin.php` to reflect the nature of the changes, this plugin follows semver versioning.
  - For small backwards comaptible changes like bug fixes update the patch version
  - For changes that add functionality without changing existing functionality update the minor version
  - For breaking or highly significant changes update the major version
3. Add a title heading for the version number above the latest updates in `CHANGELOG.md`
4. Commit and push the changes
  - If making the changes against `master` create a pull request instead and backport it to the latest stable branch
5. Go to the releases tab and create a new release from the target release branch, set the new tag name to match the updated version number

You may need to repeat this process when backporting fixes to multiple release branches.
