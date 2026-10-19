# LuxHabitat Mumbai — Channel-partner website

A luxury real-estate website with a full admin panel. Built in **plain PHP 8 + SQLite + vanilla JS** — no Node, no Composer, no database setup. Runs on Hostinger PHP hosting.

## Features

**Website**
- Home: full-screen sliding banner with a search bar (location · configuration · budget), featured projects, “why us” stats, locations, call-to-action
- All projects: live filters (location, configuration, budget, carpet area, status, keyword) and sorting (featured, price low→high or high→low, largest carpet area, newest). Shareable filter URLs.
- Project page (same layout for every project): sliding banners → elevation + overview with key facts → amenities with icons → configurations (carpet area, “Price on Request”) → floor plans (unlocked after enquiry) → gallery with lightbox → video → connectivity + Google Map + directions → EMI calculator → FAQs → project enquiry form → RERA details → similar projects. Sticky section nav and a mobile Call / WhatsApp / Enquire bar.
- E-brochure download only after the lead form pop-up is submitted
- One thank-you page shared by every form (it starts the brochure download automatically when relevant)
- Animations: preloader, Ken Burns slides, text reveals, scroll reveals, smooth scrolling, full-screen menu, custom cursor (desktop). Respects “reduce motion”.
- About, Contact, Privacy Policy, 404, sitemap.xml, robots.txt, WhatsApp button, SEO meta and schema.org data

**Admin panel** (`/admin`, default password **luxhabitat123**)
- Dashboard: lead stats and recent leads
- Projects: add / edit / duplicate / hide / delete / feature / reorder
- **Smart fill from e-brochure:** upload the developer PDF and the form fills itself (name, location, RERA, possession, towers, storeys, land, configurations with carpet areas, price, amenities, connectivity, overview, highlights). The PDF is attached as the project’s gated brochure.
  - Without an API key: built-in rule-based reader for text-based PDFs
  - With an Anthropic API key (Settings → Integrations): Claude reads the whole PDF, including image-only or scanned brochures, and writes a polished overview. Each brochure costs a few US cents to roughly $0.50, depending on page count.
- Amenities: master list of 80 real-estate amenities shown as checkboxes (searchable, select by category). Add, rename or recategorise them.
- Media: elevation, card thumbnail, banners, gallery (drag to reorder), floor plans, YouTube walkthrough. Large images are resized automatically.
- Leads: filter by project, source, status or date. Track status and notes, message on WhatsApp, export to CSV.
- Home banners, site settings, contact details, MahaRERA agent number, social links, disclaimer, analytics and conversion scripts, lead webhook (Zapier, Make, Google Sheets or CRM), email alerts and password change

## Deploy on Hostinger

1. In hPanel open **Websites → Manage → Advanced → PHP Configuration** and choose **PHP 8.1 or newer**. Make sure the `pdo_sqlite`, `gd` and `curl` extensions are enabled (they are by default).
2. Upload **everything in this folder** to `public_html/` with File Manager (zip, upload, extract) or FTP. Include the hidden files `.htaccess` and `.user.ini`.
3. Visit your domain. The database (`data/luxhabitat.sqlite`) is created on the first visit, with 6 demo projects and 3 banners.
4. Go to `yourdomain.com/admin`, log in with `luxhabitat123` and:
   - **Settings:** enter your phone, WhatsApp, email, address, **MahaRERA agent registration number** and logo. Paste an Anthropic API key for AI brochure reading.
   - **Projects:** delete or edit the demo projects and add your real ones. They use stock Unsplash photos and say “Demo Developer”.
   - **Home Banners:** upload your own hero images.
5. Optional: uncomment the HTTPS redirect in `.htaccess` once SSL is active.

Folders `data/` and `uploads/` must be writable (they are on Hostinger by default). `data/`, `includes/` and `uploads/brochures/` are blocked from direct web access, so brochures are only served through the lead gate.

If your host does not support `.htaccess` rewrites, set `PRETTY_URLS` to `false` in `includes/bootstrap.php`. Links then become `project.php?slug=…`.

## Backups
Download `data/luxhabitat.sqlite` and the `uploads/` folder. Together they hold all content and leads.

## Local development
```bash
php -S localhost:8000 dev-router.php
```
(`dev-router.php` emulates the `.htaccess` rewrites; it is not used on Hostinger.)

## Structure
```
index.php, projects.php, project.php, thank-you.php, contact.php, about.php, privacy.php, 404.php
lead.php          – single endpoint for all lead forms (saves, emails, webhook → thank-you)
download.php      – gated brochure download
includes/         – bootstrap, DB schema + seed, helpers, templates
assets/           – css, js, images, vendor (Lenis smooth scroll, pdf.js)
admin/            – admin panel (brochure-extract.php = smart fill)
data/             – SQLite database (auto-created)
uploads/          – images & brochures
```
