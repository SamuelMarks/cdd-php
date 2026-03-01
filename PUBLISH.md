# Publishing cdd-php

To publish this package to Packagist (the default package repository for PHP):

1. **Tag a Release:** Ensure all changes are committed and tag a version:
   `git tag -a v0.0.1 -m "Release v0.0.1"`
2. **Push Tags:** 
   `git push origin --tags`
3. **Packagist:** Log into [Packagist.org](https://packagist.org/), submit your GitHub repository URL, and set up a GitHub webhook for auto-updating.

## Publishing Documentation

1. **Local Server (Static Serving):**
   Run `make build_docs` to build `docs.json`. You can then copy the `docs/` directory to any web server (like Nginx or Apache) or a static host like GitHub Pages, Vercel, or Netlify.

2. **Popular Documentation Sites:**
   You can also host these docs on [ReadTheDocs](https://readthedocs.org) or [GitHub Pages](https://pages.github.com/). For GitHub Pages, enable it in your repository settings under the "Pages" tab and point it to the branch and folder (e.g., `gh-pages` branch or `/docs` folder on `main`).