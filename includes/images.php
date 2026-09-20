<?php
/**
 * Central image registry for the public site.
 *
 * Every image on the public site is referenced by a descriptive key here,
 * instead of a raw URL scattered across templates.
 *
 * 'home_hero' is a local SVG illustration in assets/images/ — reliable,
 * no network dependency. It was switched to local specifically because
 * the Unsplash URL used here previously was reported as broken more than
 * once in this project's environment, and this is the single most visible
 * image on the site (above the fold on the home page).
 *
 * Every other image below is still a real photograph hosted externally
 * (via a CDN), per explicit request. Honest tradeoff: that means those
 * depend on this environment being able to reach images.unsplash.com — if
 * it can't, those specific images (not the hero) would fail to load the
 * same way. Two things soften that: every URL requests a server-side crop
 * matching its exact display box (no wasted bandwidth), and
 * assets/js/app.js already has a global <img> error handler that hides a
 * failed image with a soft placeholder instead of a broken-image icon.
 * If one of these fails too, the most reliable fix is downloading it into
 * assets/images/ yourself and pointing that entry's 'src' at
 * BASE_URL . 'assets/images/<your-file>.jpg' — nothing else needs to change.
 */

return [

    'home_hero' => [
        'src'        => BASE_URL . 'assets/images/home-hero-campus.svg',
        'alt'        => 'Illustration of students walking across the college campus toward the main academic building',
        'width'      => 900,
        'height'     => 675,
        'eager'      => true, // above the fold — do not lazy-load
    ],

    'carousel_lecture_hall' => [
        'src'        => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=1200&h=525&q=80',
        'alt'        => 'A modern university lecture hall filled with students',
        'width'      => 1200,
        'height'     => 525,
    ],
    'carousel_campus_grounds' => [
        'src'        => 'https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?auto=format&fit=crop&w=1200&h=525&q=80',
        'alt'        => 'Students walking between buildings on the college campus grounds',
        'width'      => 1200,
        'height'     => 525,
    ],
    'carousel_library' => [
        'src'        => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=1200&h=525&q=80',
        'alt'        => 'Students studying together at a table in the campus library',
        'width'      => 1200,
        'height'     => 525,
    ],
    'carousel_graduation' => [
        'src'        => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=1200&h=525&q=80',
        'alt'        => 'Graduates celebrating at a university graduation ceremony',
        'width'      => 1200,
        'height'     => 525,
    ],

    'mosaic_cs_lab' => [
        'src'        => 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=900&h=560&q=80',
        'alt'        => 'Computer science students collaborating on a project at a shared workstation',
        'width'      => 900,
        'height'     => 560,
    ],
    'mosaic_engineering_lab' => [
        'src'        => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=600&h=400&q=80',
        'alt'        => 'Engineering students working with equipment in a campus lab',
        'width'      => 600,
        'height'     => 400,
    ],
    'mosaic_business_seminar' => [
        'src'        => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&fit=crop&w=600&h=400&q=80',
        'alt'        => 'Business administration students in a seminar discussion',
        'width'      => 600,
        'height'     => 400,
    ],

    'testimonial_registrar' => [
        'src'        => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=100&h=100&q=80',
        'alt'        => 'Portrait of a college registrar',
        'width'      => 100,
        'height'     => 100,
    ],

    'about_lecture_hall' => [
        'src'        => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=700&h=875&q=80',
        'alt'        => 'Students seated in rows in a university lecture hall, listening to an instructor',
        'width'      => 700,
        'height'     => 875,
        'eager'      => true, // primary image on the About page
    ],

];
