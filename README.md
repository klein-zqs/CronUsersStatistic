# CronUsersStatistic Plugin

## Overview

The `CronUsersStatistic` plugin records the number of users who have logged into the ILIAS system each day. This data is collected via a daily cron job and stored in the `crn_usr_statistics` database table.

## Version
v2.1.0

## Installation

1. Copy the `CronUsersStatistic` directory to `Customizing/global/plugins/Services/Cron/CronHook/`.
2. Navigate to the ILIAS administration area and go to "Plugins."
3. Install and activate the `CronUsersStatistic` plugin.

## Configuration

- **Auto Activation:** The cron job is set to activate automatically after the plugin is installed.
- **Schedule:** The job runs daily by default.

## Logging

The plugin logs the execution of the cron job, including:
- Start and end times of the job.
- Number of users recorded each day.
- Any errors encountered during execution.

Logs can be found in the standard ILIAS log files.

## Database Structure

The plugin creates a single table, `crn_usr_statistics`, with the following columns:
- `id`: Auto-incrementing primary key.
- `stat_date`: The date the statistics were recorded.
- `user_count`: Number of unique users who logged in on the specified date.
- `created_at`: Timestamp of when the record was created.

## Troubleshooting

If the plugin fails to record statistics or you encounter errors:
1. Check the ILIAS log files for detailed error messages.
2. Ensure that the cron job is correctly scheduled and activated.

## Contact

For issues, contact  [Fadi Asbih](mailto:asbih@elsa.uni-hannover.de).
