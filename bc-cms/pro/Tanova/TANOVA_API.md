# Tanova API

Base URL: `https://portal.tsokatravel.com/api/tanova`

All endpoints require a **Sanctum Bearer token**. Obtain one by authenticating against the GoTrip user API.

---

## Authentication

Include the token in every request header:

```
Authorization: Bearer <token>
```

All responses are `Content-Type: application/json`.

---

## Endpoints

### 1. List Trips

```
GET /api/tanova/trips
```

Returns the authenticated user's active trips created within the last 24 hours.

**Response 200**

```json
{
  "data": [
    {
      "id": 12,
      "title": "Trip to Cape Town — 5 days, 2 guests",
      "destination": "Cape Town",
      "start_date": "2026-06-01",
      "end_date": "2026-06-06",
      "guests": 2,
      "trip_type": "room",
      "estimated_price": "1420.00",
      "currency": "USD",
      "status": "created",
      "created_at": "2026-05-26T01:45:00.000000Z"
    }
  ]
}
```

| Field             | Type    | Description                                  |
|-------------------|---------|----------------------------------------------|
| `id`              | integer | Trip ID                                      |
| `title`           | string  | Auto-generated title                         |
| `destination`     | string  | Destination name                             |
| `start_date`      | date    | `YYYY-MM-DD`                                 |
| `end_date`        | date    | `YYYY-MM-DD`                                 |
| `guests`          | integer | Number of guests                             |
| `trip_type`       | string  | `room`, `apartment`, or `any`                |
| `estimated_price` | decimal | Total estimated cost in `currency`           |
| `currency`        | string  | ISO 4217 — currently always `USD`            |
| `status`          | string  | `created` or `booked`                        |
| `created_at`      | datetime | ISO 8601                                   |

---

### 2. Get Trip

```
GET /api/tanova/trips/{id}
```

Returns the full trip record including the generated itinerary packages.

**Path parameter**

| Parameter | Type    | Description |
|-----------|---------|-------------|
| `id`      | integer | Trip ID     |

**Response 200**

```json
{
  "data": {
    "id": 12,
    "title": "Trip to Cape Town — 5 days, 2 guests",
    "destination": "Cape Town",
    "start_date": "2026-06-01",
    "end_date": "2026-06-06",
    "guests": 2,
    "trip_type": "room",
    "estimated_price": "1420.00",
    "currency": "USD",
    "status": "created",
    "booked_package": null,
    "itinerary": [
      {
        "package": 1,
        "total_cost": 1420.00,
        "activity_cost": 820.00,
        "stay_cost": 600.00,
        "price_per_person": 710.00,
        "hotel": {
          "name": "Hippo Boutique Hotel",
          "type": "room",
          "cost_per_night": 65.00,
          "rooms": 1,
          "image": "hippo_boutique_hotel.jpg"
        },
        "itinerary": [
          {
            "day": 1,
            "date": "2026-06-01",
            "title": "Day 1",
            "weather": {
              "condition": "partly_cloudy",
              "icon": "⛅",
              "temp_min": 14,
              "temp_max": 22,
              "rain_prob": 20
            },
            "activities": [
              {
                "time": "07:30",
                "name": "Breakfast at The Pot Luck Club",
                "description": "A vibrant tapas restaurant perched atop the Old Biscuit Mill in Woodstock.",
                "duration": 1,
                "cost": 22.00,
                "type": "Restaurant",
                "included": false
              },
              {
                "time": "08:00",
                "name": "Table Mountain Aerial Cableway",
                "description": "Iconic cable car ride to the flat-topped summit with panoramic city views.",
                "duration": 3,
                "cost": 50.00,
                "type": "Outdoor",
                "included": false,
                "pax": 2
              },
              {
                "time": "19:30",
                "name": "Dinner at Greenhouse",
                "description": "Fine dining restaurant in the Cellars-Hohenort hotel serving modern South African cuisine.",
                "duration": 1.5,
                "cost": 65.00,
                "type": "Restaurant",
                "included": false
              }
            ],
            "accommodation": {
              "name": "Hippo Boutique Hotel",
              "type": "room",
              "stars": null,
              "image": "hippo_boutique_hotel.jpg"
            },
            "meals": {
              "breakfast": true,
              "lunch": false,
              "dinner": true
            }
          }
        ]
      }
    ],
    "created_at": "2026-05-26T01:45:00.000000Z",
    "updated_at": "2026-05-26T01:45:00.000000Z"
  }
}
```

**Itinerary day `weather` object**

| Field       | Type    | Description                                 |
|-------------|---------|---------------------------------------------|
| `condition` | string  | Weather condition label (7 possible values) |
| `icon`      | string  | Emoji matching the condition                |
| `temp_min`  | integer | Daily minimum temperature in Celsius        |
| `temp_max`  | integer | Daily maximum temperature in Celsius        |
| `rain_prob` | integer | Precipitation probability 0-100             |

> Possible `condition` values: `sunny`, `partly_cloudy`, `cloudy`, `foggy`, `rainy`, `snowy`, `stormy`.
> Weather data from [Open-Meteo](https://open-meteo.com/) — forecast API for future dates, archive API for past dates. No API key required.

**Activity object fields**

| Field         | Type    | Description                                               |
|---------------|---------|-----------------------------------------------------------|
| `time`        | string  | Start time `HH:MM`                                       |
| `name`        | string  | Activity or restaurant name                               |
| `description` | string  | Short description                                         |
| `duration`    | number  | Duration in hours                                         |
| `cost`        | number  | Per-person cost in USD. `0` if not applicable             |
| `type`        | string  | Activity category, e.g. `Restaurant`, `Outdoor`, `Museum` |
| `included`    | boolean | Always `false` — guests pay all costs directly            |
| `pax`         | integer | Number of guests this line applies to (activities only)   |

> **Note — Restaurants:** Breakfast (`07:30`) and dinner (`19:30`) slots are real restaurants sourced live from OpenStreetMap and described by Claude AI. They are **never included** in the package price — guests pay on their own. The `cost` field shows the estimated average spend per person.

**Errors**

| Status | Meaning                                      |
|--------|----------------------------------------------|
| 403    | Trip belongs to a different user             |
| 404    | Trip not found                               |

---

### 3. Generate Trip

```
POST /api/tanova/generate
```

Generates a new AI trip itinerary and saves it. Uses **Claude AI** (`TanovaAiService`) to build the itinerary from the GoTrip activity catalog.

**Request body** (`application/json`)

```json
{
  "destination": "Dubai",
  "start_date": "2026-07-10",
  "end_date": "2026-07-15",
  "guests": 2,
  "trip_type": "room",
  "budget": "mid-range",
  "notes": "We love water activities and fine dining."
}
```

| Field         | Type    | Required | Description                                          |
|---------------|---------|----------|------------------------------------------------------|
| `destination` | string  | Yes      | Destination name, e.g. `"Cape Town"`, `"Dubai"`      |
| `start_date`  | date    | Yes      | `YYYY-MM-DD`                                         |
| `end_date`    | date    | Yes      | `YYYY-MM-DD` — must be after `start_date`            |
| `guests`      | integer | Yes      | Minimum `1`                                          |
| `trip_type`   | string  | No       | `room` or `apartment` — defaults to any              |
| `budget`      | string  | No       | `budget`, `mid-range`, or `luxury`                   |
| `notes`       | string  | No       | Free-text preferences, max 1000 characters           |

**Response 201**

Returns the newly created trip object (same shape as [Get Trip](#2-get-trip)).

```json
{
  "data": {
    "id": 13,
    "title": "Luxury Dubai Escape",
    "destination": "Dubai",
    "status": "created",
    ...
  }
}
```

**Errors**

| Status | Body                              | Meaning                          |
|--------|-----------------------------------|----------------------------------|
| 422    | Validation error details          | Missing or invalid fields        |
| 500    | `{"error": "AI generation failed"}` | Claude AI call failed          |

---

## Trip Status Values

| Status    | Meaning                                                  |
|-----------|----------------------------------------------------------|
| `created` | Generated, not yet booked. Editable via the admin UI.    |
| `booked`  | Moved to Bookings. Locked — no further edits.            |

---

## Notes

- **Restaurant costs** shown in the itinerary are estimates sourced from OpenStreetMap + Claude AI. They are the guest's own expense and are **not** rolled into `total_cost` or `estimated_price`.
- The `estimated_price` on the list endpoint reflects activity + accommodation costs only.
- Trips are soft-deleted; deleted trips do not appear in any response.
- The 24-hour window on `GET /trips` can be adjusted server-side via the `TANOVA_WINDOW_HOURS` env variable.
