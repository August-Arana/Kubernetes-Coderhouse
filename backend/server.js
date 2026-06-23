const express = require('express');
const { Pool } = require('pg');
const app = express();
const port = 3000;
app.use(express.json());
const pool = new Pool({
  host: process.env.PGHOST || 'postgres-db-service',
  user: process.env.PGUSER || 'postgres',
  password: process.env.PGPASSWORD || 'secret',
  database: process.env.PGDATABASE || 'ecommerce'
});
pool.on('error', (err, client) => {
  console.error('Unexpected error on idle client', err);
});
app.get('/healthz', (req, res) => {
  res.status(200).send('OK');
});
app.get('/readyz', async (req, res) => {
  try {
    await pool.query('SELECT 1');
    res.status(200).send('Ready');
  } catch (err) {
    console.error('Readiness check failed: Cannot connect to DB', err);
    res.status(503).send('Not Ready: DB connection failed');
  }
});
// GET
app.get('/products', async (req, res) => {
  try {
    const result = await pool.query('SELECT * FROM products ORDER BY id');
    res.json(result.rows);
  } catch (err) {
    console.error('Error fetching products:', err);
    res.status(500).json({ error: 'Internal server error' });
    11
  }
});
// GET
app.get('/products/:id', async (req, res) => {
  const { id } = req.params;
  try {
    const result = await pool.query('SELECT * FROM products WHERE id = $1', [id]);
    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }
    res.json(result.rows[0]);
  } catch (err) {
    console.error(`Error fetching product with ID ${id}:`, err);
    res.status(500).json({ error: 'Internal server error' });
  }
});
// POST
app.post('/products', async (req, res) => {
  const { name, description, price } = req.body;
  // Validacion
  if (!name || !price) {
    return res.status(400).json({ error: 'Name and price are required' });
  }
  try {
    const result = await pool.query(
      'INSERT INTO products (name, description, price) VALUES ($1, $2, $3) RETURNING *',
      [name, description, price]
    );
    res.status(201).json(result.rows[0]);
  } catch (err) {
    console.error('Error creating product:', err);
    res.status(500).json({ error: 'Internal server error' });
  }
});
// PUT
app.put('/products/:id', async (req, res) => {
  const { id } = req.params;
  const { name, description, price } = req.body;
  // Validacion
  if (!name && !description && !price) {
    12
    return res.status(400).json({
      error: 'At least one field (name, description, price) is required for update' });
}
const fields = [];
    const values = [];
    let query = 'UPDATE products SET';
    let valueIndex = 1;
    if (name !== undefined) {
      fields.push(`name = $${valueIndex++}`);
      values.push(name);
    }
    if (description !== undefined) {
      fields.push(`description = $${valueIndex++}`);
      values.push(description);
    }
    if (price !== undefined) {
      fields.push(`price = $${valueIndex++}`);
      values.push(price);
    }
    if (fields.length === 0) {
      return res.status(400).json({ error: 'No fields provided for update' });
    }
    query += ' ' + fields.join(', ') + `, updated_at = CURRENT_TIMESTAMP WHERE id =
$${valueIndex} RETURNING *`;
    values.push(id);
    try {
      const result = await pool.query(query, values);
      if (result.rows.length === 0) {
        return res.status(404).json({ error: 'Product not found' });
      }
      res.json(result.rows[0]);
    } catch (err) {
      console.error(`Error updating product with ID ${id}:`, err);
      res.status(500).json({ error: 'Internal server error' });
    }
  });
// DELETE
13
app.delete('/products/:id', async (req, res) => {
  const { id } = req.params;
  try {
    const result = await pool.query('DELETE FROM products WHERE id = $1 RETURNING * ', [id]);
if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }
    res.json({ message: 'Product deleted successfully', deletedProduct: result.rows[0] });
  } catch (err) {
    console.error(`Error deleting product with ID ${id}:`, err);
    res.status(500).json({ error: 'Internal server error' });
  }
});
app.listen(port, () => {
  console.log(`API running at http://localhost:${port}`);
});
