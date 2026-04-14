import { useEffect, useState } from 'react'
import api from './api'

export default function App() {
  const [products, setProducts] = useState([])
  const [error, setError] = useState('')

  useEffect(() => {
    api.get('/products')
      .then((res) => {
        const data = Array.isArray(res.data?.data) ? res.data.data : []
        setProducts(data)
      })
      .catch(() => setError('Gagal mengambil produk. Pastikan API Laravel berjalan.'))
  }, [])

  return (
    <main style={{ fontFamily: 'sans-serif', padding: 20 }}>
      <h1>Toko Baju Dashboard</h1>
      {error && <p>{error}</p>}
      <ul>
        {products.map((product) => (
          <li key={product.id}>{product.name}</li>
        ))}
      </ul>
    </main>
  )
}
