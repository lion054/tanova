<?php

use Modules\Api\Docs\Doc;

/** Areas of the API, in the order the reference shows them. */
Doc::tag('Getting started', 'Who you are and what your key can do.');
Doc::tag('Listings', 'Tours, hotels, cars, boats, spaces, events, flights and visas: read, publish or hide, and edit.');
Doc::tag('Seats and options', 'Departures with seat counts, the Classic / Signature / Sublime options, and add-ons.');
Doc::tag('Bookings', 'Create and read bookings, and everything you do on one: status, travellers, payments, refunds, documents, check-in.');
Doc::tag('Guests', 'Guest accounts, guest sign-in, and a guest\'s own bookings, payments and waitlist. Called with your key plus the guest\'s token. Sign-in, sign-up and anything that changes data need a secret key (or a key allowed `customers:write`).');
Doc::tag('Customers', 'Your customer records: search, notes, tags, birthdays.');
Doc::tag('Loyalty', 'The earning rule, tiers, members and manual point adjustments.');
Doc::tag('Waitlist', 'Guests waiting for a full day, and telling them when a seat opens.');
Doc::tag('Invoices', 'Invoices, their lines and payments, and PDFs.');
Doc::tag('Messages', 'Scheduled messages, campaigns and occasions such as birthdays.');
Doc::tag('Insights', 'Analytics, occupancy and the trending and bestseller shelves.');
Doc::tag('Marketplace', 'Which experiences AI assistants can discover and book.');
Doc::tag('Suppliers', 'The operators and suppliers you buy from.');
Doc::tag('Planner', 'The Tanova trip planner, day plans, and the concierge inbox with its AI drafts.');
Doc::tag('Webhooks', 'Be told when things happen, instead of asking.');
Doc::tag('Catalogue', 'Everything one destination offers, in one request.');
