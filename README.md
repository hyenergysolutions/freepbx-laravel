# FreePBX Laravel

A Laravel package for integrating with FreePBX's GraphQL API.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

```bash
composer require hyenergysolutions/freepbx-laravel
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=freepbx-config
```

Add these environment variables to your `.env` file:

```env
FREEPBX_URL=http://your-pbx-ip:83
FREEPBX_CLIENT_ID=your-client-id
FREEPBX_CLIENT_SECRET=your-client-secret
```

You can create API credentials in FreePBX under **Admin > API > Applications**.

## Usage

### Using the Facade

```php
use HyEnergySolutions\FreePBX\Facades\FreePBX;

// Get all extensions
$extensions = FreePBX::getExtensions();

// Get all call flows (day/night mode)
$callFlows = FreePBX::getCallFlows();

// Get all queues
$queues = FreePBX::getQueues();
```

### Using Dependency Injection

```php
use HyEnergySolutions\FreePBX\FreePBX;

class PBXController extends Controller
{
    public function __construct(
        private FreePBX $freepbx
    ) {}

    public function index()
    {
        return $this->freepbx->getExtensions();
    }
}
```

## Available Methods

### `getExtensions(): Collection`

Returns all extensions with user details including name, voicemail settings, caller ID, and call forwarding configuration.

### `getCallFlows(): Collection`

Returns all day/night call flows with their current state.

### `getQueues(): Collection`

Returns all call queues with their configuration.

## Error Handling

The package throws `FreePBXException` for API errors:

```php
use HyEnergySolutions\FreePBX\Exceptions\FreePBXException;

try {
    $extensions = FreePBX::getExtensions();
} catch (FreePBXException $e) {
    // Handle the error
    Log::error($e->getMessage());
}
```

## License

MIT License. See [LICENSE](LICENSE) for details.
