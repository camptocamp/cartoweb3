      <!-- layerFilter -->

      <complexType name="LayerFilterRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="criteria_keys" type="types:ArrayOfString"/>
          <element name="criteria_values" type="types:ArrayOfString"/>
        </all>
      </complexType>

      <complexType name="LayerFilterResult" />
