      <!-- outline -->

      <complexType name="OutlineRequest">
        <all>
          <element name="shapes" type="types:ArrayOfShape"/>
          <element name="maskMode" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="OutlineResult">
        <all>
          <element name="area" type="xsd:double"/>
        </all>
      </complexType>

      <complexType name="OutlineInit">
        <all>
        </all>
      </complexType>      