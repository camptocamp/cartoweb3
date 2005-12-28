      <!-- projectplugin -->

      <complexType name="ProjectpluginRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="message" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ProjectpluginResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="shuffledMessage" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ProjectpluginInit">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="initMessage" type="xsd:string"/>
        </all>
      </complexType>
 