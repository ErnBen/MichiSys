package com.snpp.MichiSys.Impl;

import com.snpp.MichiSys.entity.Venta;
import com.snpp.MichiSys.repository.VentaRepository;
import com.snpp.MichiSys.service.VentaService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.util.List;

@Service
public class VentaServiceImpl implements VentaService {

    @Autowired
    private VentaRepository ventaRepository;

    @Override
    public List<Venta> listar() {
        return ventaRepository.findAll();
    }

    @Override
    public Venta buscarPorId(Long id) {
        return ventaRepository.findById(id).orElse(null);
    }

    @Override
    public Venta guardar(Venta venta) {
        return ventaRepository.save(venta);
    }

    @Override
    public Venta actualizar(Long id, Venta venta) {

        Venta ventaActual = ventaRepository.findById(id).orElse(null);

        if (ventaActual != null) {

            ventaActual.setFecha(venta.getFecha());
            ventaActual.setTotal(venta.getTotal());
            ventaActual.setFormaPago(venta.getFormaPago());

            return ventaRepository.save(ventaActual);
        }

        return null;
    }

    @Override
    public void eliminar(Long id) {
        ventaRepository.deleteById(id);
        
    }


}
