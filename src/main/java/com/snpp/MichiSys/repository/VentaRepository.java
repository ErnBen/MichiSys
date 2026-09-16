package com.snpp.MichiSys.repository;

import com.snpp.MichiSys.entity.Venta;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.time.LocalDate;
import java.util.List;

@Repository
public interface VentaRepository extends JpaRepository<Venta, Long> {

    // Buscar ventas por fecha
    List<Venta> findByFecha(LocalDate fecha);

}