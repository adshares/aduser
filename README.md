# aduser

Implementation of adUser service in Adshares Network

adUser provides data about a visiting user to improve adPay and adSelect results

# API
### Tracking pixel
`GET /setimg/{requestId}`
* requestId - user provided request id used in future queries about user data

Return tracking pixel and associate it with a provided requestId

### Get data
`GET /get/{requestId}`
* requestId - request id associated with the user 

Return data about the user
```
[
  'user_id' => '12345',
    'request_id' => 'abcdef',
    'human_score' => 0.5,
    'keywords' => [
        'tor' => false, 
        'age' => 24,
        ...
    ],
]
```

### Get info
`GET /info`
Return info about adUser service 

```
[
  'pixel_url' => 'https://example.com/setimg/:id',
  'data_url' => 'https://example.com/get/:id'
  'schema' => []
]
