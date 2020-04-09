const app = require('express')();

app.get('/', (req, res) => res.send('Hello World!'));

app.listen(process.env.PORT || 3000);
