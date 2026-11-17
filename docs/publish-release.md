# To publish a new release

There are two release lines, each with its own branch:

| Branch    | Versions | Matomo | `plugin.json` `require.matomo` |
|-----------|----------|--------|--------------------------------|
| `main`    | 1.x      | 5      | `>=5.0.0,<6.0.0-b1`            |
| `6.x-dev` | 2.x      | 6      | `>=6.0.0-b1,<7.0.0-b1`         |

The Matomo Marketplace offers every user the newest release whose range
matches their Matomo, so Matomo 5 users keep getting 1.x updates.

## 1. Prepare the release in a pull request

1. Update the version in `plugin.json`.
2. Update the `Version:` line in `DataExport.php`.
3. Add an entry to `CHANGELOG.md`: a line with just the version, followed by
   the changes. This becomes the GitHub release notes.
4. Update `README.md` and `docs/index.md` if needed.
5. Merge the pull request once CI is green.

## 2. Publish

Go to **Actions → Release → Run workflow**, choose the branch (`main` for 1.x,
`6.x-dev` for 2.x) and run it. Alternatively, push a tag yourself:

```sh
git tag v1.2.1 && git push origin v1.2.1
```

The Release workflow then:

- checks that `plugin.json`, `DataExport.php` and the tag have the same
  version, that `CHANGELOG.md` has an entry for it, and that the Matomo range
  matches the release line (1.x must target Matomo 5, 2.x Matomo 6),
- runs the full CI,
- creates the tag (when started from the Actions tab) and a GitHub release
  with the CHANGELOG entry as notes and `DataExport-<version>.zip`, which can
  be uploaded in Matomo under *Plugins → Upload a plugin*.
  A 1.x release made after 2.0.0 is not marked as GitHub's "Latest" release.

If a check fails, nothing is tagged or published: fix it, merge, and run the
workflow again.

## 3. Marketplace

The Marketplace picks up the new tag through its webhook. Check
https://plugins.matomo.org/DataExport after a few minutes.
